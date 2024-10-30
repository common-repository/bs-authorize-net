<?php
/**
 * BS_Anet_WC_PayPal class
 * @author Bipin
 */
class BS_Anet_WC_PayPal extends WC_Payment_Gateway {

    /**
     * __construct
     */
    public function __construct() {
        $this->id = BS_ANET_WC_PAYPAL_PLUGIN_ID;
        $this->method_title = __("BS Authorize.net paypal", 'bs_anet_wc');
        $this->method_description = sprintf(__('Authorize.net Paypal payment method.Api login,Transaction key and Gateway mode can be adjusted <a href="%s">here</a>.', 'bs_anet_wc'), admin_url('admin.php?page=wc-settings&tab=checkout&section=bs_anet_wc_cc'));
        $this->title = __("Authorize.net Paypal", 'bs_anet_wc');
        $this->icon = BS_ANET_WC_BASEURL_ASSETS . 'images/paymentmethods/paypal.svg';
        $this->has_fields = false;
        $this->supports = array('products', 'refunds');
        $this->init_form_fields();
        $this->init_settings();
        if (!is_admin()):
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->enabled = trim($this->settings['enabled']);
        endif;
        $paypal = BS_Anet_WC_paypalHelper::getInstance();
        $paypal->register_paypal_urls();
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
    }

    /**
     * init_form_fields
     *
     * @return void
     */
    public function init_form_fields() {
        BS_Anet_WC_formhelper::backend_settings_form($this);
    }

    /**
     * process_payment
     *
     * @param [type] $order_id
     * @return void
     */
    public function process_payment($order_id) {
        global $woocommerce;
        $customer_order = new WC_Order($order_id);
        $payment = BS_Anet_WC_payment::getInstance();
        $response = $payment->createTransaction($order_id, 'P');
        return $payment->wc_response($response, $customer_order, $this->get_return_url($customer_order));
    }

    /**
     * process_refund
     *
     * @param [type] $order_id
     * @param [type] $amount
     * @param string $reason
     * @return void
     */
    public function process_refund($order_id, $amount = null, $reason = '') {
        $payment = BS_Anet_WC_payment::getInstance();
        return $payment->createRefundTransaction($order_id, $amount, $reason);
    }


    /**
     * is_available
     *
     * @return boolean
     */
    public function is_available() {
      return  BS_Anet_WC_formhelper::is_available($this); 
    }
}
