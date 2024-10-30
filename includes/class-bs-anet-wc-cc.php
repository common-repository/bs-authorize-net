<?php
/**
 * BS_Anet_WC_Cc
 * @author Bipin
 */
class BS_Anet_WC_Cc extends WC_Payment_Gateway_CC {

    /**
     * __construct
     */
    public function __construct() {
        $this->id = BS_ANET_WC_CC_PLUGIN_ID;
        $this->method_title = __("BS Authorize.net credit card", 'bs_anet_wc');
        $this->method_description = sprintf(__('Authorize.net credit card payment method.<a href="%s">Sign up</a> for an Authorize.net account.', 'bs_anet_wc'), 'https://www.authorize.net/sign-up/');
        $this->title = __("Authorize.net Credit Card", 'bs_anet_wc');
        // $this->icon = BS_ANET_WC_BASEURL_ASSETS.'images/frontend/cards.png';
        $this->has_fields = true;
        $this->supports = array('products', 'refunds', 'add_payment_method', 'tokenization');
        $this->init_form_fields();
        $this->init_settings();
        if (!is_admin()):
            $this->title = esc_html(trim($this->settings['title']));
            $this->description = esc_html(trim($this->settings['description']));
            $this->enabled = trim($this->settings['enabled']);
        endif;
        
        if (is_admin()) {
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }
        add_filter('woocommerce_credit_card_form_fields', array('BS_Anet_WC_formhelper', 'payment_form_cc_fields'), 10, 2);
        load_frontend_scripts();
    }

    /**
     * payment_fields
     *
     * @return void
     */
    public function payment_fields() {
        BS_Anet_WC_formhelper::cc_payment_form($this);
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
        $response = $payment->createTransaction($order_id, 'C');
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

    function process_admin_options(){

       if(BS_Anet_WC_formvalidator::processGatewaySettings($this))
       {
        parent::process_admin_options();
       }
       else
       {
           return false;
       }
        

     
    }

    /**
     * validate_fields
     *
     * @return void
     */
    public function validate_fields() {
        return BS_Anet_WC_formvalidator::validateCard();
    }

    /**
     * add_payment_method
     *
     * @return void
     */
    public function add_payment_method() {
        $payment = BS_Anet_WC_payment::getInstance();
        return $payment->add_payment_method('C');
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
     * get_icon
     *
     * @return void
     */
    public function get_icon() {
        $icons_str = '';
        $cards = is_method_available('C');
        if($cards)
        foreach ($cards['cards'] as $icon) {
            $icons_str.= $icon;
        }
        return apply_filters('woocommerce_gateway_icon', $icons_str, $this->id);
    }
}
