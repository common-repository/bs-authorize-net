<?php
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * BS_Anet_WC_customer
 * @author Bipin
 */
class BS_Anet_WC_customer {
    private static $instance;
    var $helper, $settings;

    /**
     * getInstance
     *
     * @return void
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BS_Anet_WC_customer();
        }
        return self::$instance;
    }

    /**
     * __construct
     */
    function __construct() {
        $this->helper = BS_Anet_WC_helper::getInstance();
    }
    
    /**
     * getCustomerprofile
     *
     * @param [type] $user_id
     * @return void
     */
    public function getCustomerprofile($user_id) {
        $return = array('state' => 0, 'data' => '');
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication) {
            $request = new AnetAPI\GetCustomerProfileRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setMerchantCustomerId($user_id);
            $controller = new AnetController\GetCustomerProfileController($request);
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                $return['state'] = 1;
                $custData = array();
                $custData['profile_id'] = $response->getProfile()->getCustomerProfileId();
                $custData['payment_profiles'] = $response->getProfile()->getPaymentProfiles();
                $return['data'] = $custData;
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                $return['data'] = $errorMessages[0]->getCode() . ":" . $errorMessages[0]->getText();
            }
        }
        return $return;
    }
    
    /**
     * createCustomer
     *
     * @param [type] $user_id
     * @return void
     */
    public function createCustomer($user_id) {
        $return = array('state' => 0, 'data' => '');
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication) {
            $refId = 'ref' . time();
            $wp_user = get_userdata($user_id);
            $customerProfile = new AnetAPI\CustomerProfileType();
            $customerProfile->setMerchantCustomerId($user_id);
            $customerProfile->setEmail($wp_user->user_email);
            $customerProfile->setDescription($wp_user->user_nicename);
            $request = new AnetAPI\CreateCustomerProfileRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refId);
            $request->setProfile($customerProfile);
            $controller = new AnetController\CreateCustomerProfileController($request);
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                $return['state'] = 1;
                $custData = array();
                $custData['profile_id'] = $response->getCustomerProfileId();
                $custData['payment_profiles'] = $response->getCustomerPaymentProfileIdList();
                $return['data'] = $custData;
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                $return['data'] = $errorMessages[0]->getCode() . ":" . $errorMessages[0]->getText();
            }
        }
        return $return;
    }

    /**
     * createCustomerPaymentProfile
     *
     * @param [type] $user_id
     * @param [type] $pmType
     * @return void
     */
    public function createCustomerPaymentProfile($user_id, $pmType) {
        $resp = array('state' => 'failure', 'data' => 'Unknown error occured.Please try again later.');
        if ($user_id) {
            $remoteCustomer = $this->getCustomerprofile($user_id);
            if (!$remoteCustomer['state']) {
                $remoteCustomer = $this->createCustomer($user_id);
            }
            if ($remoteCustomer['state']) {
                //create customer payment profile
                $merchantAuthentication = $this->helper->getAuthAuthentication();
                if ($merchantAuthentication) {
                    $refId = 'ref' . time();
                    $paymentOne = $this->paymentMethod($pmType);
                    $paymentprofile = new AnetAPI\CustomerPaymentProfileType();
                    $paymentprofile->setCustomerType('individual');
                    $paymentprofile->setPayment($paymentOne);
                    $paymentprofile->setDefaultPaymentProfile(false);
                    $paymentprofiles[] = $paymentprofile;
                    $paymentprofilerequest = new AnetAPI\CreateCustomerPaymentProfileRequest();
                    $paymentprofilerequest->setMerchantAuthentication($merchantAuthentication);
                    $paymentprofilerequest->setRefId($refId);
                    $paymentprofilerequest->setCustomerProfileId($remoteCustomer['data']['profile_id']);
                    $paymentprofilerequest->setPaymentProfile($paymentprofile);
                    $paymentprofilerequest->setValidationMode("testMode");
                    $controller = new AnetController\CreateCustomerPaymentProfileController($paymentprofilerequest);
                    $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
                    if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                        $resp['state'] = 'success';
                        $cdata = array();
                        $cdata['user_id'] = $user_id;
                        if ('C' === $pmType) {
                            extract(parse_request_cc());
                            $card = CreditCard::validCreditCard($cardnumber);
                            $cdata['cid'] = $response->getCustomerProfileId();
                            $cdata['pid'] = $response->getCustomerPaymentProfileId();
                            $cdata['expiry_year'] = $cardExpiryYear;
                            $cdata['expiry_month'] = $cardExpiryMonth;
                            $cdata['last_four'] = substr($cardnumber, -4);
                            $cdata['card_type'] = $card['type'];
                        } elseif ('E' == $pmType) {
                            extract(parse_request_echeck());
                            $cdata['cid'] = $response->getCustomerProfileId();
                            $cdata['pid'] = $response->getCustomerPaymentProfileId();
                            $cdata['last_four'] = substr($anet_ecaccnum, -4);
                        }
                        $resp['data'] = $cdata;
                    } else {
                        $errorMessages = $response->getMessages()->getMessage();
                        $resp['data'] = $errorMessages[0]->getCode() . ':' . $errorMessages[0]->getText();
                    }
                }
            }
        }
        return $resp;
    }

    /**
     * setDefaultPaymentProfile
     *
     * @param [type] $customer_id
     * @param [type] $payment_profile_id
     * @param [type] $type
     * @return void
     */
    public function setDefaultPaymentProfile($customer_id, $payment_profile_id, $type) {
        $return = array('state' => false, 'data' => '');
        $paymentmethod = $this->getCustomerPaymentMethod($customer_id, $payment_profile_id, $type);
        if ($paymentmethod['state']) {
            $paymentprofile = new AnetAPI\CustomerPaymentProfileExType();
            $paymentprofile->setCustomerPaymentProfileId($payment_profile_id);
            $paymentprofile->setDefaultPaymentProfile(true);
            $paymentprofile->setPayment($paymentmethod['data']);
            // Submit a UpdatePaymentProfileRequest
            $request = new AnetAPI\UpdateCustomerPaymentProfileRequest();
            $merchantAuthentication = $this->helper->getAuthAuthentication();
            if ($merchantAuthentication) {
                $request->setMerchantAuthentication($merchantAuthentication);
                $request->setCustomerProfileId($customer_id);
                $request->setPaymentProfile($paymentprofile);
                $controller = new AnetController\UpdateCustomerPaymentProfileController($request);
                $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
                if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                    $Message = $response->getMessages()->getMessage();
                    $return['state'] = true;
                    $return['data'] = $Message[0]->getCode() . ":" . $Message[0]->getText();
                } else {
                    $errorMessages = $response->getMessages()->getMessage();
                    $return['data'] = $errorMessages[0]->getCode() . ":" . $errorMessages[0]->getText();
                }
            }
        } else {
            $return['data'] = $paymentmethod['data'];
        }
        return $return;
    }

    /**
     * getCustomerPaymentMethod
     *
     * @param [type] $customer_id
     * @param [type] $payment_profile_id
     * @param [type] $type
     * @return void
     */
    private function getCustomerPaymentMethod($customer_id, $payment_profile_id, $type) {
        $return = array('state' => false, 'data' => '');
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication) {
            // Set the transaction's refId
            $refId = 'ref' . time();
            $request = new AnetAPI\GetCustomerPaymentProfileRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refId);
            $request->setCustomerProfileId($customer_id);
            $request->setCustomerPaymentProfileId($payment_profile_id);
            $controller = new AnetController\GetCustomerPaymentProfileController($request);
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                $paymentmethod = new AnetAPI\PaymentType();
                if ('C' === $type):
                    $creditCardEx = $response->getPaymentProfile()->getPayment()->getCreditCard();
                    $creditCard = new AnetAPI\CreditCardType();
                    $creditCard->setCardNumber($creditCardEx->getCardNumber());
                    $creditCard->setExpirationDate($creditCardEx->getExpirationDate());
                    $paymentmethod->setCreditCard($creditCard);
                elseif ('E' === $type):
                    $bankAccountEx = $response->getPaymentProfile()->getPayment()->getBankAccount();
                    $bankAccount = new AnetAPI\BankAccountType();
                    $bankAccount->setAccountType($bankAccountEx->getAccountType());
                    $bankAccount->setRoutingNumber($bankAccountEx->getRoutingNumber());
                    $bankAccount->setAccountNumber($bankAccountEx->getAccountNumber());
                    $bankAccount->setNameOnAccount($bankAccountEx->getNameOnAccount());
                    $bankAccount->setEcheckType($bankAccountEx->getEcheckType());
                    $bankAccount->setBankName($bankAccountEx->getBankName());
                    $paymentmethod->setBankAccount($bankAccount);
                endif;
                $return['state'] = true;
                $return['data'] = $paymentmethod;
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                $return['data'] = $errorMessages[0]->getCode() . ":" . $errorMessages[0]->getText();
            }
        }
        return $return;
    }

    /**
     * deletePaymentProfile
     *
     * @param [type] $customer_id
     * @param [type] $payment_profile_id
     * @return void
     */
    public function deletePaymentProfile($customer_id, $payment_profile_id) {
        $return = array('state' => false, 'data' => '');
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication) {
            // Set the transaction's refId
            $refId = 'ref' . time();
            // Use an existing payment profile ID for this Merchant name and Transaction key
            $request = new AnetAPI\DeleteCustomerPaymentProfileRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setCustomerProfileId($customer_id);
            $request->setCustomerPaymentProfileId($payment_profile_id);
            $controller = new AnetController\DeleteCustomerPaymentProfileController($request);
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            if (($response != null) && ($response->getMessages()->getResultCode() == "Ok")) {
                $return['state'] = true;
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                $return['data'] = $errorMessages[0]->getCode() . ":" . $errorMessages[0]->getText();
            }
        }
        return $return;
    }

    /**
     * paymentMethod
     *
     * @param [type] $pmType
     * @return void
     */
    private function paymentMethod($pmType) {
        $paymentOne = new AnetAPI\PaymentType();
        if ($pmType == 'C') {
            extract(parse_request_cc());
            $cardExpiry = $cardExpiryYear . '-' . $cardExpiryMonth;
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber($cardnumber);
            $creditCard->setExpirationDate($cardExpiry);
            $creditCard->setCardCode($cvv);
            $paymentOne->setCreditCard($creditCard);
        } elseif ($pmType == 'E') {
            extract(parse_request_echeck());
            $bankAccount = new AnetAPI\BankAccountType();
            $bankAccount->setAccountType($anet_ecacctype);
            $bankAccount->setEcheckType('WEB');
            $bankAccount->setRoutingNumber($anet_ecroutingnumber);
            $bankAccount->setAccountNumber($anet_ecaccnum);
            $bankAccount->setNameOnAccount($anet_ecnoacc);
            $bankAccount->setBankName($anet_ecbankname);
            $paymentOne->setBankAccount($bankAccount);
        }
        return $paymentOne;
    }
    /**
     * delete_customer
     *
     * @return void
     */  
    public function delete_customer($user_id)
    {
       
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if($merchantAuthentication)
        {
            $customer =  $this->getCustomerprofile($user_id);
            if($customer['state'])
            {
            $customer= $customer['data'];
            $profile_id = $customer['profile_id'];
            $refId = 'ref' . time(); 
            $request = new AnetAPI\DeleteCustomerProfileRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setCustomerProfileId($user_id);
            $controller = new AnetController\DeleteCustomerProfileController($request);
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            if (($response != null) && ($response->getMessages()->getResultCode() == "Ok") )
	         {
		      return true;
	         }
	          else
	         {
                $errorMessages = $response->getMessages()->getMessage();
		        return false;
	        }

            }
            
        }
    } 
}
?>