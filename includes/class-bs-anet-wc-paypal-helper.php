<?php
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * BS_Anet_WC_paypalHelper class
 * @author Bipin
 */
class BS_Anet_WC_paypalHelper {
    private static $instance;
    var $logContext;
    var $helper;

    /**
     * getInstance
     *
     * @return void
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BS_Anet_WC_paypalHelper();
        }
        return self::$instance;
    }

    /**
     * __construct
     */
    function __construct() {
        $this->helper = BS_Anet_WC_helper::getInstance();
        $this->logContext = $context = array('source' => 'woocommerce_gateway_authorizenet_paypal');
    }

    /**
     * register_paypal_urls
     *
     * @return void
     */
    public function register_paypal_urls() {
        add_action('woocommerce_api_paypal_return_success', array($this, 'paypal_return_success'));
        add_action('woocommerce_api_paypal_return_cancelled', array($this, 'paypal_return_cancelled'));
    }

    /**
     * paypal_return_success
     *
     * @return void
     */
    public function paypal_return_success() {
        $token = sanitize_text_field($_GET['token']);
        $customer_id = sanitize_text_field($_GET['cid']);
        $order_id = sanitize_text_field($_GET['oid']);
        $PayerID = sanitize_text_field($_GET['PayerID']);
        if (!empty($PayerID) && !empty($customer_id) && !empty($PayerID)) {
            global $woocommerce;
            $customer_order = new WC_Order($order_id);
            $order_token = $customer_order->get_meta('wc_anet_temp_token');
            if ($customer_order && $order_token == $token) {
                $tid = get_post_meta($customer_order->get_id(), 'wc_anet_temp_id', true);
                $resp = $this->helper->payPalAuthorizeCaptureContinued($customer_order, $PayerID, $tid);
                if ($resp['state']) {
                    $customer_order->add_order_note($resp['data']);
                    $customer_order->payment_complete($resp['trans_id']);
                    $woocommerce->cart->empty_cart();
                    $customer_order->reduce_order_stock();
                    wp_redirect($this->get_return_url($customer_order));
                    exit;
                } else {
                    $customer_order->add_order_note($resp['data']);
                    $shopUrl = get_permalink(wc_get_page_id('shop'));
                    wc_add_notice($resp['data'], 'error');
                    wp_redirect($shopUrl);
                    exit;
                }
            } else {
                wp_redirect(home_url());
                exit();
            }
        } else {
            wp_redirect(home_url());
            exit();
        }
    }

    /**
     * paypal_return_cancelled
     *
     * @return void
     */
    public function paypal_return_cancelled() {
        $token = sanitize_text_field($_GET['token']);
        $customer_id = sanitize_text_field($_GET['cid']);
        $order_id = sanitize_text_field($_GET['oid']);
        $PayerID = sanitize_text_field($_GET['PayerID']);
        global $woocommerce;
        $customer_order = new WC_Order($order_id);
        $order_token = $customer_order->get_meta('wc_anet_temp_token');
        $temptransId = $customer_order->get_meta('wc_anet_temp_id');
        if ($customer_order) {
            $woocommerce->cart->empty_cart();
            $shopUrl = get_permalink(wc_get_page_id('shop'));
            if ("pending" === $customer_order->get_status()) {
                $customer_order->update_status('cancelled');
                $customer_order->add_order_note('Order cancelled by customer. Transaction Id: ' . $temptransId);
                wc_add_notice('Order cancelled.', 'error');
                delete_post_meta($customer_order->get_id(), 'wc_anet_pm_payerid');
                delete_post_meta($customer_order->get_id(), 'wc_anet_temp_id');
                delete_post_meta($customer_order->get_id(), 'wc_anet_temp_token');
            }
            wp_redirect($shopUrl);
            exit;
        } else {
            wp_redirect(home_url());
            exit();
        }
    }

    /**
     * get_return_url
     *
     * @param [type] $order
     * @return void
     */
    private function get_return_url($order = null) {
        if ($order) {
            $return_url = $order->get_checkout_order_received_url();
        } else {
            $return_url = wc_get_endpoint_url('order-received', '', wc_get_page_permalink('checkout'));
        }
        if (is_ssl() || 'yes' === get_option('woocommerce_force_ssl_checkout')) {
            $return_url = str_replace('http:', 'https:', $return_url);
        }
        return apply_filters('woocommerce_get_return_url', $return_url, $order);
    }
}
?>