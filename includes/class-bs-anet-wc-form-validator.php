
<?php
/**
 * BS_Anet_WC_formvalidator
 * @author Bipin
 */
class BS_Anet_WC_formvalidator {
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
            self::$instance = new BS_Anet_WC_formvalidator();
        }
        return self::$instance;
    }

    /**
     * validateCard
     *
     * @return void
     */
    public static function validateCard() {
        $remote = isset($_GET['remote']) ? wc_clean($_GET['remote']) : 0;
        $errors = array("state" => 1, "messages" => array());
        if (hasConfiguration()) {
            //if existing method
            if (isExistingMethod('C')) {
                return self::validateToken('C');
            } elseif (isAcceptSuite()) {
                return self::validateOpaqueData();
            } else {
                extract(parse_request_cc());
                $card = CreditCard::validCreditCard($cardnumber);
                if ($card['valid'] != 1) {
                    $errors['state'] = 0;
                    $errors['messages'][] = __("Invalid card number.", "bs_anet_wc");
                    $errors['messages'][] = __("Invalid card code.", "bs_anet_wc");
                }
                if (!CreditCard::validDate($cardExpiryYear, $cardExpiryMonth)) {
                    $errors['state'] = 0;
                    $errors['messages'][] = __("Invalid expiration date.", "bs_anet_wc");
                }
                if ($card['valid'] == 1) {
                    if (!CreditCard::validCvc($cvv, $card['type'])) {
                        $errors['state'] = 0;
                        $errors['messages'][] = __("Invalid card code.", "bs_anet_wc");
                    }
                }
            }
        } else {
            $errors['state'] = 0;
            $errors['messages'][] = __('Invalid gateway configuration.Please contact the site administrator.', "bs_anet_wc");
        }
        if ($remote) {
            echo json_encode($errors);
            wp_die();
        }
        if ($errors['state'] == 0) {
            foreach ($errors['messages'] as $message) {
                wc_add_notice($message, 'error');
            }
            return false;
        } else {
            return true;
        }
    }

    /**
     * validateEcheck
     *
     * @return void
     */
    public static function validateEcheck() {
        $remote = isset($_GET['remote']) ? wc_clean($_GET['remote']) : 0;
        $errors = array("state" => 1, "messages" => array());
        if (hasConfiguration()) {

            
            //if existing method
            if (isExistingMethod('E')) {
                return self::validateToken('E');
            } elseif (isAcceptSuite()) {
                return self::validateOpaqueData();
            } else {
                
                extract(parse_request_echeck());

               if (!Echeck::validAccountnumber($anet_ecaccnum)) {
                    $errors['state'] = 0;
                    $errors['messages'][] = __("Invalid accounting number.", "bs_anet_wc");
                }
                if (!Echeck::validRoutingnumber($anet_ecroutingnumber)) {
                    $errors['state'] = 0;
                    $errors['messages'][] = __("Invalid routing number.Routing number must have 9 digits.", "bs_anet_wc");
                }
                if (!Echeck::validNameonaccount($anet_ecnoacc)) {
                    $errors['state'] = 0;
                    $errors['messages'][] = __("Invalid name on account.", "bs_anet_wc");
                }
                if (!Echeck::validBankname($anet_ecbankname)) {
                    $errors['state'] = 0;
                    $errors['messages'][] = __("Invalid bank name.", "bs_anet_wc");
                }
                if (!Echeck::validAccountype($anet_ecacctype)) {
                    $errors['state'] = 0;
                    $errors['messages'][] = __("invalid account type.", "bs_anet_wc");
                }

                
            }
        } else {
            $errors['state'] = 0;
            $errors['messages'][] = __("Invalid gateway configuration.Please contact the site administrator.", "bs_anet_wc");
        }
        if ($remote) {
            echo json_encode($errors);
            wp_die();
        }

        if ($errors['state'] == 0) {
            foreach ($errors['messages'] as $message) {
                wc_add_notice($message, 'error');
            }
            return false;
        } else {
            
            return true;
        }
    }

    /**
     * validateOpaqueData
     *
     * @return void
     */
    public static function validateOpaqueData() {
        extract(parse_request_opaquedata());
        $errors = array("state" => 1, "messages" => array());
        if (!$dataDescriptor && !$dataValue) {
            $errors['state'] = 0;
            $errors['messages'][] = __("Invalid payment data.", "bs_anet_wc");
        }
        if ($errors['state'] == 0) {
            foreach ($errors['messages'] as $message) {
                wc_add_notice($message, 'error');
            }
            return false;
        } else {
            return true;
        }
    }

    /**
     * validateToken
     *
     * @param [type] $type
     * @return void
     */
    public static function validateToken($type) {
        $tokenId = isExistingMethod($type);
        $token = WC_Payment_Tokens::get($tokenId);
        $method = 'C' === $type ? BS_ANET_WC_CC_PLUGIN_ID : ('E' === $type ? BS_ANET_WC_ECHECK_PLUGIN_ID : null);
        if ($token) {
            if ($method === $token->get_gateway_id() && $token->get_user_id() === get_current_user_id()) {
                return true;
            }
        }
        wc_add_notice(__("Invalid payment method token.", "bs_anet_wc"), 'error');
        return false;
    }

    /**
     * processGatewaySettings
     *
     * @param [type] $plugin
     * @return void
     */
    public static function processGatewaySettings($plugin)
    {
        $return = false;
        $settings = array();
        $settings['api_login']   = wc_clean($_POST['woocommerce_'.BS_ANET_WC_CC_PLUGIN_ID.'_api_login']);
        $settings['trans_key']   = wc_clean($_POST['woocommerce_'.BS_ANET_WC_CC_PLUGIN_ID.'_trans_key']);
        $settings['environment'] = isset($_POST['woocommerce_'.BS_ANET_WC_CC_PLUGIN_ID.'_environment'])?'yes':'no';
        $_POST['woocommerce_'.BS_ANET_WC_CC_PLUGIN_ID.'_public_client_key'] = '';

        if(!empty($settings['api_login']) && !empty($settings['trans_key']) )
        {
                    $payment = BS_Anet_WC_payment::getInstance();
                    $merchant =  $payment->verifyAnet($settings);
                    if(!$merchant['state'])
                    {
                        //reset old settings 
                        $plugin->update_option('public_client_key','');
                        $plugin->update_option('api_login','');
                        $plugin->update_option('trans_key','');
                        WC_Admin_Settings::add_error( __('Authorize.net merchant verification failed.Please enter valid API Login and Transaction Key.') );
                    }
                    else
                    {
                        $_POST['woocommerce_'.BS_ANET_WC_CC_PLUGIN_ID.'_public_client_key'] = $merchant['data']['public_client_key'];
                        $return = true;
                    }
        }
        else
        {
            WC_Admin_Settings::add_error( __('Authorize.net API Login and Authorize.net Transaction Key are required fields,please fill them.') );
        }
        return  $return;
    }
}
?>