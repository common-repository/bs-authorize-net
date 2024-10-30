<?php
/**
 * WC_Gateway_anet_Token_Helper
 * @author Bipin
 */
class WC_Gateway_anet_Token_Helper {

    /**
     * instance
     *
     * @var [type]
     */
    private static $instance;

    /**
     * getInstance
     *
     * @return void
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new WC_Gateway_anet_Token_Helper();
        }
        return self::$instance;
    }

    /**
     * saveToken
     *
     * @param [type] $ttype
     * @param [type] $data
     * @return void
     */
    public function saveToken($ttype, $data) {
        if ('C' == $ttype) {
            $token = new WC_Payment_Token_anetcc();
            $token->set_token($data['pid']);
            $token->set_default(false);
            $token->set_gateway_id(BS_ANET_WC_CC_PLUGIN_ID);
            $token->set_customer_profile_id($data['cid']);
            $token->set_card_type($data['card_type']);
            $token->set_last4($data['last_four']);
            $token->set_expiry_month($data['expiry_month']);
            $token->set_expiry_year($data['expiry_year']);
            $token->set_user_id($data['user_id']);
            return $token->save();
        }
        if ('E' == $ttype) {
            $token = new WC_Payment_Token_anetecheck();
            $token->set_token($data['pid']);
            $token->set_default(false);
            $token->set_gateway_id(BS_ANET_WC_ECHECK_PLUGIN_ID);
            $token->set_customer_profile_id($data['cid']);
            $token->set_last4($data['last_four']);
            $token->set_user_id($data['user_id']);
            return $token->save();
        }
    }

    /**
     * woocommerce_get_customer_payment_anet_tokens
     *
     * @param array $tokens
     * @param [type] $customer_id
     * @param [type] $gateway_id
     * @return void
     */
    public function woocommerce_get_customer_payment_anet_tokens($tokens = array(), $customer_id, $gateway_id) {
        $wp_user = get_userdata(1);
        if (is_add_payment_method_page() && is_user_logged_in()) {
            $stored_tokens = array();
            foreach ($tokens as $token) {
                if (BS_ANET_WC_CC_PLUGIN_ID === $token->get_gateway_id() || BS_ANET_WC_ECHECK_PLUGIN_ID === $token->get_gateway_id()) $stored_tokens[] = $token->get_token();
            }
            $customer = BS_Anet_WC_customer::getInstance();
            $customer_profile = $customer->getCustomerprofile($customer_id);
            if ($customer_profile['state']) {
                $payment_profiles = isset($customer_profile['data']['payment_profiles']) && !empty($customer_profile['data']['payment_profiles']) ? $customer_profile['data']['payment_profiles'] : null;
                if ($payment_profiles) {
                    foreach ($payment_profiles as $payment_profile) {
                        $defaultPaymentProfile = ($payment_profile->getDefaultPaymentProfile() == 1);
                        $customerProfileId = $customer_profile['data']['profile_id'];
                        $customerPaymentProfileId = $payment_profile->getCustomerPaymentProfileId();
                        if (!in_array($customerPaymentProfileId, $stored_tokens)) {
                            $payment = $payment_profile->getPayment();
                            $bankAccount = $payment->getBankAccount();
                            $creditCard = $payment->getCreditCard();
                            if ($creditCard) {
                                $token = new WC_Payment_Token_anetcc();
                                $token->set_token($customerPaymentProfileId);
                                $token->set_default($defaultPaymentProfile);
                                $token->set_gateway_id(BS_ANET_WC_CC_PLUGIN_ID);
                                $token->set_customer_profile_id($customerProfileId);
                                $token->set_card_type($creditCard->getCardType());
                                $token->set_last4(substr($creditCard->getCardNumber(), -4));
                                $token->set_expiry_month('xx');
                                $token->set_expiry_year('xxxx');
                                $token->set_user_id($customer_id);
                                $token->save();
                                $tokens[$token->get_id() ] = $token;
                            } elseif ($bankAccount) {
                                $token = new WC_Payment_Token_anetecheck();
                                $token->set_token($customerPaymentProfileId);
                                $token->set_default($defaultPaymentProfile);
                                $token->set_gateway_id(BS_ANET_WC_ECHECK_PLUGIN_ID);
                                $token->set_customer_profile_id($customerProfileId);
                                $token->set_last4(substr($bankAccount->getAccountNumber(), -4));
                                $token->set_user_id($customer_id);
                                $token->save();
                                $tokens[$token->get_id() ] = $token;
                            }
                        }
                    }
                }
            }
        }
        return $tokens;
    }
    /**
     * wc_get_account_saved_payment_methods_list_item_anet
     *
     * @param [type] $item
     * @param [type] $payment_token
     * @return void
     */
    public function wc_get_account_saved_payment_methods_list_item_anet($item, $payment_token) {
        if (BS_ANET_WC_CC_PLUGIN_ID === $payment_token->get_gateway_id()) {
            $card_type = $payment_token->get_card_type();
            $item['method']['last4'] = $payment_token->get_last4();
            $item['method']['brand'] = (!empty($card_type) ? ucfirst($card_type) : esc_html__('Credit card', 'woocommerce'));
            $item['expires'] = $payment_token->get_expiry_month() . '/' . substr($payment_token->get_expiry_year(), -2);
        } elseif (BS_ANET_WC_ECHECK_PLUGIN_ID === $payment_token->get_gateway_id()) {
            $item['method']['last4'] = $payment_token->get_last4();
            $item['method']['brand'] = esc_html__('eCheck', 'woocommerce');
        }
        return $item;
    }

    /**
     * payment_token_set_default_anet
     *
     * @param [type] $token_id
     * @return void
     */
    public function payment_token_set_default_anet($token_id) {
        $token = WC_Payment_Tokens::get($token_id);
        if (BS_ANET_WC_CC_PLUGIN_ID === $token->get_gateway_id() || BS_ANET_WC_ECHECK_PLUGIN_ID === $token->get_gateway_id()) {
            $customerhelper = BS_Anet_WC_customer::getInstance();
            $customer_id = $token->get_customer_profile_id();
            $payment_profile_id = $token->get_token();
            $type = BS_ANET_WC_CC_PLUGIN_ID === $token->get_gateway_id() ? 'C' : (BS_ANET_WC_ECHECK_PLUGIN_ID === $token->get_gateway_id() ? 'E' : null);
            $result = $customerhelper->setDefaultPaymentProfile($customer_id, $payment_profile_id, $type);
            return $result['state'];
        }
    }
    /**
     * payment_token_delete_pm_anet
     *
     * @param [type] $token_id
     * @param [type] $token
     * @return void
     */
    public function payment_token_delete_pm_anet($token_id, $token) {
        if (BS_ANET_WC_CC_PLUGIN_ID === $token->get_gateway_id() || BS_ANET_WC_ECHECK_PLUGIN_ID === $token->get_gateway_id()) {
            $customerhelper = BS_Anet_WC_customer::getInstance();
            $customer_id = $token->get_customer_profile_id();
            $payment_profile_id = $token->get_token();
            $type = BS_ANET_WC_CC_PLUGIN_ID === $token->get_gateway_id() ? 'C' : (BS_ANET_WC_ECHECK_PLUGIN_ID === $token->get_gateway_id() ? 'E' : null);
            $result = $customerhelper->deletePaymentProfile($customer_id, $payment_profile_id, $type);
            return $result['state'];
        }
    }
}
