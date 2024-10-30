<?php
/**
 * BS_Anet_WC_formhelper
 * @author Bipin
 */
class BS_Anet_WC_formhelper {
    private static $instance;
    var $me = null;
	var $merchantInfo = null;
	
	/**
	 * getInstance
	 *
	 * @return void
	 */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BS_Anet_WC_formhelper();
        }
        return self::$instance;
	}
	
	/**
	 * __construct
	 */
    private function __construct() {
        add_filter('woocommerce_form_field_hidden', array($this, 'add_form_field_hidden', 10, 3));
	}
	
	/**
	 * backend_settings_form
	 *
	 * @param [type] $plugin
	 * @return void
	 */
    public static function backend_settings_form($plugin) {
        $isCC = is_a($plugin, 'BS_Anet_WC_Cc');
        $isPP = is_a($plugin, 'BS_Anet_WC_PayPal');
        $isEC = is_a($plugin, 'BS_Anet_WC_Echeck');
        $plugin->form_fields = array('enabled' => array('title' => __('Enable / Disable', 'bs_anet_wc'), 'label' => __('Enable this payment gateway', 'bs_anet_wc'), 'type' => 'checkbox', 'default' => 'no',), 'title' => array('title' => __('Title', 'bs_anet_wc'), 'type' => 'text', 'desc_tip' => __('Payment title of checkout process.', 'bs_anet_wc'),), 'description' => array('title' => __('Description', 'bs_anet_wc'), 'type' => 'textarea', 'desc_tip' => __('Payment description of checkout process.', 'bs_anet_wc'), 'css' => 'max-width:450px;'));
        if ($isCC):
            $plugin->form_fields['title']['default'] = __('Credit cards', 'bs_anet_wc');
            $plugin->form_fields['description']['default'] = __('Pay via Credit cards', 'bs_anet_wc');
            $plugin->form_fields['api_login'] = array('title' => __('Authorize.net API Login', 'bs_anet_wc'), 'type' => 'text', 'desc_tip' => __('This is the API Login provided by Authorize.net when you signed up for an account.', 'bs_anet_wc'),);
            $plugin->form_fields['trans_key'] = array('title' => __('Authorize.net Transaction Key', 'bs_anet_wc'), 'type' => 'password', 'desc_tip' => __('This is the Transaction Key provided by Authorize.net when you signed up for an account.', 'bs_anet_wc'),);
            $plugin->form_fields['environment'] = array('title' => __('Authorize.net Test Mode', 'bs_anet_wc'), 'label' => __('Enable Test Mode', 'bs_anet_wc'), 'type' => 'checkbox', 'description' => __('This is the test mode of gateway.', 'bs_anet_wc'), 'default' => 'yes',);
            $plugin->form_fields['public_client_key'] = array('id' => 'public_client_key', 'type' => 'text', 'title' => __('Public client key', 'public_client_key'), 'description' => __('Accept suite public client key.This field is auto populated when you save the settings.'), 'custom_attributes' => array('readonly' => 'readonly'));
        endif;
        if ($isEC):
            $plugin->form_fields['title']['default'] = __('Echeck', 'bs_anet_wc');
            $plugin->form_fields['description']['default'] = __('Pay via Echeck', 'bs_anet_wc');
        endif;
        if ($isPP):
            $plugin->form_fields['title']['default'] = __('Paypal', 'bs_anet_wc');
            $plugin->form_fields['description']['default'] = __('Pay via Paypal', 'bs_anet_wc');
        endif;
	}
	
	/**
	 * payment_form_echeck_fields
	 *
	 * @param [type] $default_fields
	 * @param [type] $id
	 * @return void
	 */
    public static function payment_form_echeck_fields($default_fields, $id) {
        //account name
        $default_fields['account-name'] = '<p class="form-row form-row-first">
		                                    <label for="' . esc_attr($id) . '-account-name">' . esc_html__('Account name', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
		                                     <input id="' . esc_attr($id) . '-account-name" class="input-text wc-echeck-form-account-name" type="text" maxlength="22" autocomplete="off" name="' . esc_attr($id) . '-account-name" />
	                                       </p>';
        //bank name
        $default_fields['bank-name'] = '<p class="form-row form-row-last">
											<label for="' . esc_attr($id) . '-bank-name">' . esc_html__('Bank name', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
		 									<input id="' . esc_attr($id) . '-bank-name" class="input-text wc-echeck-form-bank-name" type="text" maxlength="50" autocomplete="off" name="' . esc_attr($id) . '-bank-name" />
	   									 </p>';
        //account type
        $default_fields['account-type'] = '<p class="form-row form-row-wide">
											<label for="' . esc_attr($id) . '-account-type">' . esc_html__('Account type', 'woocommerce') . '&nbsp;<span class="required">*</span></label>
											 <select name="' . esc_attr($id) . '-account-type" id="' . esc_attr($id) . '-account-type" class="select input-text" autocomplete="off" data-placeholder="">
							                 <option value="savings">Savings</option><option value="checking">Checking</option>
						                      </select>
										 </p>';
        return $default_fields;
	}
	
	/**
	 * payment_form_cc_fields
	 *
	 * @param [type] $default_fields
	 * @param [type] $id
	 * @return void
	 */
    public static function payment_form_cc_fields($default_fields, $id) {
        //No need as of now
        return $default_fields;
	}
	
	/**
	 * payment_form_echeck
	 *
	 * @param [type] $echeck
	 * @return void
	 */
    public static function payment_form_echeck($echeck) {
        $helper = BS_Anet_WC_helper::getinstance();
        $settings = $helper->getParams();
        $advanced_settings = BS_Anet_WC_settings::getInstance();
        if ($echeck->description) {
            if ('yes' === $settings->environment) {
                $echeck->description.= __('<br>Test mode enabled.In test mode, you can use the Routing number: 121042882 Account number:'.rand(10000,999999999999).' Account name:John Doe Bank name:Wells Fargo Bank NA and Account type: Checking .In test mode transactions above $100 amount generate declined response.', 'bs_anet_wc');
                $echeck->description = trim($echeck->description);
            }
            echo wpautop(wp_kses_post($echeck->description));
        }
        if ($echeck->supports('tokenization') && is_checkout()) {
            $echeck->tokenization_script();
            $echeck->saved_payment_methods();
            $echeck->form();
            if($advanced_settings->get_option('save_paymethod')==='on')
            $echeck->save_payment_method_checkbox();
        } else {
            $echeck->form();
        }
	}
	
	/**
	 * cc_payment_form
	 *
	 * @param [type] $cc
	 * @return void
	 */
    public static function cc_payment_form($cc) {
        $settings = BS_Anet_WC_settings::getInstance();
        
        if ($cc->description) {
            if ('yes' === $cc->settings['environment']) {
                $cc->description.='<br>'. __('Test mode enabled. In test mode, you can use the card numbers 5424000000000015,4111111111111111 cvv any number and expiration date some future date', 'bs_anet_wc');
                $cc->description = trim($cc->description);
            }
            echo wpautop(wp_kses_post($cc->description));
        }
        if ($settings->get_option('card_animation')==='yes') {
            add_action('woocommerce_credit_card_form_start', function ($id) {
                if (BS_ANET_WC_CC_PLUGIN_ID === $id) {
                    echo cardAnimation();
                }
            });
        }
        if ($cc->supports('tokenization') && is_checkout()) {
            $cc->tokenization_script();
            $cc->saved_payment_methods();
            $cc->form();
            if($settings->get_option('save_paymethod')==='on')
            $cc->save_payment_method_checkbox();
        } else {
            $cc->form();
        }
    }

    public static function is_available($plugin)
    {
        
        $helper = BS_Anet_WC_helper::getInstance();
        $params = $helper->getParams();
        if('yes'===$plugin->enabled && !empty($params))
        {
            if(!is_add_payment_method_page())
            {
                return true;
            }
            else
            {
                $advanced_settings = BS_Anet_WC_settings::getInstance();
                return $advanced_settings->get_option('save_paymethod')==='on';
            }
        }
    }
    
}
?>