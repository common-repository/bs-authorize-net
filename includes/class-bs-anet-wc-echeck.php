<?php
/**
 * BS_Anet_WC_Echeck
 * @author Bipin
 */
class BS_Anet_WC_Echeck extends WC_Payment_Gateway_ECheck {

    /**
     * __construct
     */
    public function __construct() {
        $this->id = BS_ANET_WC_ECHECK_PLUGIN_ID;
        $this->method_title = __("BS Authorize.net Echeck", 'bs_anet_wc');
        $this->method_description = sprintf(__('Authorize.net Echeck payment method.Api login,Transaction key and Gateway mode can be adjusted <a href="%s">here</a>.', 'bs_anet_wc'), admin_url('admin.php?page=wc-settings&tab=checkout&section=bs_anet_wc_cc'));
        $this->title = __("Authorize.net Echeck", 'bs_anet_wc');
        $this->icon = BS_ANET_WC_BASEURL_ASSETS . 'images/paymentmethods/echeck.svg';
        $this->has_fields = true;
        $this->supports = array('products', 'refunds', 'add_payment_method', 'tokenization');
        $this->init_form_fields();
        $this->init_settings();
        if (!is_admin()):
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->enabled = trim($this->settings['enabled']);
        endif;
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
        add_filter('woocommerce_echeck_form_fields', array('BS_Anet_WC_formhelper', 'payment_form_echeck_fields'), 10, 2);
        load_frontend_scripts();
    }

    /**
     * payment_fields
     *
     * @return void
     */
    public function payment_fields() {
        BS_Anet_WC_formhelper::payment_form_echeck($this);
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
     * field_name
     *
     * @param [type] $name
     * @return void
     */
    public function field_name($name) {
        return ' name="' . esc_attr($this->id . '-' . $name) . '" ';
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
        $response = $payment->createTransaction($order_id, 'E');
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
     * validate_fields
     *
     * @return void
     */
    public function validate_fields() {

        return BS_Anet_WC_formvalidator::validateEcheck();
    }

    /**
     * is_available
     *
     * @return boolean
     */
    public function is_available() {
      return  BS_Anet_WC_formhelper::is_available($this); 
    }

    /**
     * add_payment_method
     *
     * @return void
     */
    public function add_payment_method() {
        $payment = BS_Anet_WC_payment::getInstance();
        return $payment->add_payment_method('E');
    }
}
