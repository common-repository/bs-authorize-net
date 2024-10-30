<?php
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * BS_Anet_WC_helper
 * @author Bipin
 */
class BS_Anet_WC_helper {
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
            self::$instance = new BS_Anet_WC_helper();
        }
        return self::$instance;
    }
    
    /**
     * getMerchantinfo
     *
     * @return void
     */
    public function getMerchantinfo() {
        $merchantAuthentication = $this->getAuthAuthentication();
        //one time execution
        if ($merchantAuthentication && !$this->merchantInfo) {
            $refId = 'ref' . time();
            $request = new AnetAPI\GetMerchantDetailsRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $controller = new AnetController\GetMerchantDetailsController($request);
            $this->merchantInfo = $controller->executeWithApiResponse($this->getEnvironment());
        }
        return $this->merchantInfo;
    }

    /**
     * getAuthAuthentication
     *
     * @return void
     */
    public function getAuthAuthentication() {
        $return = false;
        $me = $this->getParams();
        if ($me) {
            $api_login = $me->api_login;
            $trans_key = $me->trans_key;
            if (!empty($api_login) && !empty($trans_key)) {
                $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                $merchantAuthentication->setName($api_login);
                $merchantAuthentication->setTransactionKey($trans_key);
                $return = $merchantAuthentication;
            }
        }
        return $return;
    }

    /**
     * getParams
     *
     * @return void
     */
    public function getParams() {
        if ($this->me == null) {
            $settings = (object)get_option('woocommerce_' . BS_ANET_WC_CC_PLUGIN_ID . '_settings', array());
           if (!empty($settings) && property_exists($settings,'api_login') && property_exists($settings,'trans_key') && property_exists($settings,'environment'))
           {
            if(!empty($settings->api_login) && !empty($settings->trans_key) && !empty($settings->environment))   
            $this->me = $settings;
           } 
           
        }
        return $this->me;
    }

    /**
     * getEnvironment
     *
     * @return void
     */
    public function getEnvironment() {
        $me = $this->getParams();
        $environment = $me->environment == 'yes' ? \net\authorize\api\constants\ANetEnvironment::SANDBOX : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
        return $environment;
    }

    /**
     * processTransactionData
     *
     * @param [type] $transaction
     * @return void
     */
    public function processTransactionData($transaction) {
        $OldTransaction = $transaction->getTransaction();

        $transdata = array('getTransId' => $OldTransaction->getTransId(), 'getRefTransId' => $OldTransaction->getRefTransId(), 'getSplitTenderId' => $OldTransaction->getSplitTenderId(), 'getSubmitTimeUTC' => date_format($OldTransaction->getSubmitTimeUTC(), 'd M Y g:i:s A'), 'getSubmitTimeLocal' => date_format($OldTransaction->getSubmitTimeLocal(), 'd M Y g:i:s A'), 'getTransactionType' => $OldTransaction->getTransactionType(), 'getTransactionStatus' => $OldTransaction->getTransactionStatus(), 'getResponseCode' => $OldTransaction->getResponseCode(), 'getResponseReasonCode' => $OldTransaction->getResponseReasonCode(), 'getSubscription' => $OldTransaction->getSubscription(), 'getResponseReasonDescription' => $OldTransaction->getResponseReasonDescription(), 'getAuthCode' => $OldTransaction->getAuthCode(), 'getAVSResponse' => $OldTransaction->getAVSResponse(), 'getCardCodeResponse' => $OldTransaction->getCardCodeResponse(), 'getCAVVResponse' => $OldTransaction->getCAVVResponse(), 'getFDSFilterAction' => $OldTransaction->getFDSFilterAction(), 'getFDSFilters' => $OldTransaction->getFDSFilters(), 'getBatch' => $OldTransaction->getBatch(), 'getOrder' => $OldTransaction->getOrder(), 'getRequestedAmount' => $OldTransaction->getRequestedAmount(), 'getAuthAmount' => $OldTransaction->getAuthAmount(), 'getSettleAmount' => $OldTransaction->getSettleAmount(), 'getTax' => $OldTransaction->getTax(), 'getShipping' => $OldTransaction->getShipping(), 'getDuty' => $OldTransaction->getDuty(), 'getLineItems' => $OldTransaction->getLineItems(), 'getPrepaidBalanceRemaining' => $OldTransaction->getPrepaidBalanceRemaining(), 'getTaxExempt' => $OldTransaction->getTaxExempt(), 'getPayment' => $OldTransaction->getPayment(), 'getCustomer' => $OldTransaction->getCustomer(), 'getProfile' => $OldTransaction->getProfile(), 'getBillTo' => $OldTransaction->getBillTo(), 'getShipTo' => $OldTransaction->getShipTo(), 'getRecurringBilling' => $OldTransaction->getRecurringBilling(), 'getCustomerIP' => $OldTransaction->getCustomerIP(), 'getProduct' => $OldTransaction->getProduct(), 'getEntryMode' => $OldTransaction->getEntryMode(), 'getMarketType' => $OldTransaction->getMarketType(), 'getMobileDeviceId' => $OldTransaction->getMobileDeviceId(), 'getCustomerSignature' => $OldTransaction->getCustomerSignature(), 'getReturnedItems' => $OldTransaction->getReturnedItems(), 'getSolution' => $OldTransaction->getSolution(), 'getEmvDetails' => $OldTransaction->getEmvDetails(), 'getProfile' => $OldTransaction->getProfile(), 'getSurcharge' => $OldTransaction->getSurcharge(), 'getEmployeeId' => $OldTransaction->getEmployeeId(), 'getTip' => $OldTransaction->getTip(), 'getOtherTax' => $OldTransaction->getOtherTax(), 'getShipFrom' => $OldTransaction->getShipFrom(), 'authAmount' => $OldTransaction->getAuthAmount(), 'settleAmount' => $OldTransaction->getSettleAmount(), 'currency' => $this->getMerchantinfo()->getCurrencies() [0]);
        //add wc order
        $transdata['paymethod'] = null;
        $transdata['pay_type'] = null;
        $payment = $OldTransaction->getPayment();
        //fetch payment info
        if ($payment) {
            $transdata['paymethod'] = $payment->getCreditCard();
            $transdata['pay_type'] = "card";
            if (!$transdata['paymethod']) {
                $transdata['pay_type'] = "echeck";
                $transdata['paymethod'] = $payment->getBankAccount();
                if (!$transdata['paymethod']->getAccountNumber()) {
                    $transdata['pay_type'] = "paypal";
                    $paypalDets = $this->getPaypalTransaction($OldTransaction->getTransId());
                    $transdata['paymethod'] = $paypalDets['state'] ? $paypalDets['data'] : null;
                }
            }
        } else {
            $transdata['pay_type'] = "paypal";
            $paypalDets = $this->getPaypalTransaction($OldTransaction->getTransId());
            $transdata['paymethod'] = $paypalDets['state'] ? $paypalDets['data'] : null;
        }
        //fetch woocomerce order info
        $transdata['wc_order'] = false;
        $transactorder = $OldTransaction->getOrder();
        if ($transactorder) {
            $oid = $transactorder->getInvoiceNumber();
            if ($oid) {
                global $woocommerce;
                $order = wc_get_order($oid);

                
                if ($order) {
                    $ptype = $order->get_meta('_wc_anet_pm');
                    $transdata['wc_order'] = $order;
                    $transdata['wc_order_items'] = $order->get_items();
                    $transdata['currency']  = $order->get_currency();
                }
            }
        }
        return $transdata;
    }

    /**
     * createCardtransaction
     *
     * @param [type] $customer_order
     * @return void
     */
    public function createCardtransaction($customer_order) {
        extract(parse_request_cc());
        $cardExpiry = $cardExpiryYear . '-' . $cardExpiryMonth;
        $paymentOne = new AnetAPI\PaymentType();
        if (isAcceptSuite()) {
            extract(parse_request_opaquedata());
            $opaqueData = new AnetAPI\OpaqueDataType();
            $opaqueData->setDataDescriptor($dataDescriptor);
            $opaqueData->setDataValue($dataValue);
            $paymentOne->setOpaqueData($opaqueData);
        } else {
            $creditCard = new AnetAPI\CreditCardType();
            $creditCard->setCardNumber($cardnumber);
            $creditCard->setExpirationDate($cardExpiry);
            $creditCard->setCardCode($cvv);
            $paymentOne->setCreditCard($creditCard);
        }
        return $this->prepareResponse($customer_order, $paymentOne, "authCaptureTransaction");
    }

    /**
     * createEcheckTransaction
     *
     * @param [type] $customer_order
     * @return void
     */
    public function createEcheckTransaction($customer_order) {
        extract(parse_request_echeck());
        $paymentBank = new AnetAPI\PaymentType();
        if (isAcceptSuite()) {
            extract(parse_request_opaquedata());
            $opaqueData = new AnetAPI\OpaqueDataType();
            $opaqueData->setDataDescriptor($dataDescriptor);
            $opaqueData->setDataValue($dataValue);
            $paymentBank->setOpaqueData($opaqueData);
        } else {
            $bankAccount = new AnetAPI\BankAccountType();
            $bankAccount->setAccountType($anet_ecacctype);
            $bankAccount->setEcheckType('WEB');
            $bankAccount->setRoutingNumber($anet_ecroutingnumber);
            $bankAccount->setAccountNumber($anet_ecaccnum);
            $bankAccount->setNameOnAccount($anet_ecnoacc);
            $bankAccount->setBankName($anet_ecbankname);
            $paymentBank->setBankAccount($bankAccount);
        }
        return $this->prepareResponse($customer_order, $paymentBank, "authCaptureTransaction");
    }

    /**
     * createpaypalTransaction
     *
     * @param [type] $customer_order
     * @return void
     */
    public function createpaypalTransaction($customer_order) {
        $payPalType = new AnetAPI\PayPalType();
        $payPalType->setSuccessUrl(get_site_url() . "/wc-api/paypal_return_success?oid=" . $customer_order->id . '&cid=' . $customer_order->customer_id);
        $payPalType->setCancelUrl(get_site_url() . "/wc-api/paypal_return_cancelled?oid=" . $customer_order->id . '&cid=' . $customer_order->customer_id);
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setPayPal($payPalType);
        $resp = new StdClass();
        $resp->paypal = true;
        $resp->error = false;
        $response = $this->prepareResponse($customer_order, $paymentOne, "authCaptureTransaction");
        if ($response != null) {
            if ("Ok" === $response->getMessages()->getResultCode()) {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $resp->respcode = $tresponse->getResponseCode();
                    $resp->sau = $tresponse->getSecureAcceptance()->getSecureAcceptanceUrl();
                    $resp->tid = $tresponse->getTransId();
                    $resp->code = $tresponse->getMessages() [0]->getCode();
                    $resp->desc = $tresponse->getMessages() [0]->getDescription();
                    $parts = parse_url($resp->sau);
                    parse_str($parts['query'], $query);
                    $token = $query['token'];
                    update_post_meta($customer_order->get_id(), 'wc_anet_temp_token', $token);
                    update_post_meta($customer_order->get_id(), 'wc_anet_temp_id', $resp->tid);
                    $resp->error = false;
                } else {
                    if ($tresponse->getErrors() != null) {
                        $resp->error = $tresponse->getErrors() [0]->getErrorCode() . ":" . $tresponse->getErrors() [0]->getErrorText();
                    }
                }
            } else {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getErrors() != null) {
                    $resp->error = $tresponse->getErrors() [0]->getErrorCode() . ":" . $tresponse->getErrors() [0]->getErrorText();
                } else {
                    $resp->error = $response->getMessages()->getMessage() [0]->getCode() . ":" . $response->getMessages()->getMessage() [0]->getText();
                }
            }
        } else {
            $resp->error = "No response returned from gateway.";
        }
        return $resp;
    }

    /**
     * payPalAuthorizeCaptureContinued
     *
     * @param [type] $customer_order
     * @param [type] $PayerID
     * @param [type] $tid
     * @return void
     */
    public function payPalAuthorizeCaptureContinued($customer_order, $PayerID, $tid) {
        $resp = array('state' => 0, 'data' => null);
        $payPalType = new AnetAPI\PayPalType();
        $payPalType->setPayerID($PayerID);
        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setPayPal($payPalType);
        $response = $this->prepareResponse($customer_order, $paymentOne, "authCaptureContinueTransaction", $tid);
        if ($response != null) {
            if ("Ok" === $response->getMessages()->getResultCode()) {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    $respType = array(1 => "Approved", 2 => "Declined", 3 => "Error", 5 => "Need Payer Consent");
                    if ($tresponse->getResponseCode() == 1) {
                        $resp['state'] = 1;
                        $tranid = $tresponse->getTransId();
                        $transRespCode = $tresponse->getMessages() [0]->getCode();
                        $description = $tresponse->getMessages() [0]->getDescription();
                        $note = "Transaction ID:" . $tranid . " Response code: " . $transRespCode . " Description: " . $description;
                        $resp['data'] = $note;
                        $resp['trans_id'] = $tranid;
                    } else {
                        $resp['data'] = "Transaction failed: " . $respType[$tresponse->getResponseCode() ];
                    }
                } else {
                    if ($tresponse->getErrors() != null) {
                        $resp['data'] = $tresponse->getErrors() [0]->getErrorCode() . ':' . $tresponse->getErrors() [0]->getErrorText();
                    }
                }
            } else {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getErrors() != null) {
                    $resp['data'] = $tresponse->getErrors() [0]->getErrorCode() . ':' . $tresponse->getErrors() [0]->getErrorText();
                } else {
                    $resp['data'] = $response->getMessages()->getMessage() [0]->getCode() . ':' . $response->getMessages()->getMessage() [0]->getText();
                }
            }
        } else {
            $resp['data'] = "No response returned";
        }
        //delete temp tokens
        delete_post_meta($customer_order->get_id(), 'wc_anet_pm_payerid');
        delete_post_meta($customer_order->get_id(), 'wc_anet_temp_id');
        delete_post_meta($customer_order->get_id(), 'wc_anet_temp_token');
        return $resp;
    }

    /**
     * getPaypalTransaction
     *
     * @param [type] $tid
     * @return void
     */
    public function getPaypalTransaction($tid) {
        $return = array('state' => 0, 'data' => null);
        $merchantAuthentication = $this->getAuthAuthentication();
        if ($merchantAuthentication) {
            $refId = 'ref' . time();
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("getDetailsTransaction");
            $transactionRequestType->setRefTransId($tid);
            $payPalType = new AnetAPI\PayPalType();
            $payPalType->setCancelUrl(get_site_url());
            $payPalType->setSuccessUrl(get_site_url());
            $paymentOne = new AnetAPI\PaymentType();
            $paymentOne->setPayPal($payPalType);
            $transactionRequestType->setPayment($paymentOne);
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refId);
            $request->setTransactionRequest($transactionRequestType);
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse($this->getEnvironment());
            if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                $transaction = $response->getTransactionResponse();
                $secureAcceptance = $transaction->getSecureAcceptance();
                if ($secureAcceptance) {
                    $return['state'] = 1;
                    $return['data'] = array("payer_email" => $secureAcceptance->getPayerEmail(), 'payer_id' => $secureAcceptance->getPayerID());
                }
            } else {
                $return['data'] = "Unable to locate transaction.";
            }
        }
        return $return;
    }

    /**
     * performVoid
     *
     * @param [type] $transactionId
     * @param [type] $paymentType
     * @return void
     */
    public function performVoid($transactionId, $paymentType = null) {
        $return = array('state' => 0, 'data' => null);
        $merchantAuthentication = $this->getAuthAuthentication();
        if ($merchantAuthentication) {
            $refId = 'ref' . time();
            $transactionRequest = new AnetAPI\TransactionRequestType();
            $transactionRequest->setTransactionType("voidTransaction");
            if ($paymentType) $transactionRequest->setPayment($paymentType);
            $transactionRequest->setRefTransId($transactionId);
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refId);
            $request->setTransactionRequest($transactionRequest);
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse($this->getEnvironment());
            if ($response != null) {
                if ("Ok" === $response->getMessages()->getResultCode()) {
                    $tresponse = $response->getTransactionResponse();
                    if ($tresponse != null && $tresponse->getMessages() != null) {
                        $return['state'] = 1;
                        $return['data'] = "Transaction voided,Gateway response description:" . $tresponse->getMessages() [0]->getDescription();
                    } else {
                        if ($tresponse->getErrors() != null) {
                            $return['data'] = $tresponse->getErrors() [0]->getErrorCode() . ':' . $tresponse->getErrors() [0]->getErrorText();
                        }
                    }
                } else {
                    $tresponse = $response->getTransactionResponse();
                    if ($tresponse != null && $tresponse->getErrors() != null) {
                        $return['data'] = $tresponse->getErrors() [0]->getErrorCode() . ':' . $tresponse->getErrors() [0]->getErrorText();
                    } else {
                        $return['data'] = $response->getMessages()->getMessage() [0]->getCode() . ':' . $response->getMessages()->getMessage() [0]->getText();
                    }
                }
            } else {
                $return['data'] = "No response returned";
            }
        }
        return $return;
    }

    /**
     * performRefund
     *
     * @param [type] $paymentOne
     * @param [type] $transactionId
     * @param [type] $amount
     * @param [type] $customer_order
     * @return void
     */
    public function performRefund($paymentOne, $transactionId, $amount, $customer_order) {
        $return = array('state' => 0, 'data' => null);
        $merchantAuthentication = $this->getAuthAuthentication();
        $advanced_settings = BS_Anet_WC_settings::getInstance();
        if ($merchantAuthentication) {
            $refId = 'ref' . time();
            $items = $customer_order->get_items();
            $refTransId = $transactionId;
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($customer_order->id);
            $orderDesc = array();
            foreach ($items as $item) {
                $orderDesc[] = $item->get_product_id();
            }
            $order->setDescription(implode(",", $orderDesc));
            $countryBilling = WC()->countries->countries[$customer_order->get_billing_country() ];
            $stateBilling = WC()->countries->get_states($customer_order->get_billing_country()) [$customer_order->get_billing_state() ];
            $customerAddressBilling = new AnetAPI\CustomerAddressType();
            $customerAddressBilling->setFirstName($customer_order->get_billing_first_name());
            $customerAddressBilling->setLastName($customer_order->get_billing_last_name());
            $customerAddressBilling->setCompany($customer_order->get_billing_company());
            $customerAddressBilling->setAddress($customer_order->get_billing_address_1() . '' . $customer_order->get_billing_address_2());
            $customerAddressBilling->setCity($customer_order->get_billing_city());
            $customerAddressBilling->setState($stateBilling);
            $customerAddressBilling->setZip($customer_order->get_billing_postcode());
            $customerAddressBilling->setCountry($countryBilling);
            $countryShipping = WC()->countries->countries[$customer_order->get_shipping_country() ];
            $stateShipping = WC()->countries->get_states($customer_order->get_shipping_country()) [$customer_order->get_shipping_state() ];
            $customerAddressShipping = new AnetAPI\CustomerAddressType();
            $customerAddressShipping->setFirstName($customer_order->get_shipping_first_name());
            $customerAddressShipping->setLastName($customer_order->get_shipping_last_name());
            $customerAddressShipping->setCompany($customer_order->get_shipping_company());
            $customerAddressShipping->setAddress($customer_order->get_shipping_address_1() . '' . $customer_order->get_shipping_address_2());
            $customerAddressShipping->setCity($customer_order->get_shipping_city());
            $customerAddressShipping->setState($stateShipping);
            $customerAddressShipping->setZip($customer_order->get_shipping_postcode());
            $customerAddressShipping->setCountry($countryShipping);
            $customerData = new AnetAPI\CustomerDataType();
            $customerData->setType("individual");
            $customerData->setId($customer_order->get_customer_id());
            $customerData->setEmail($customer_order->get_billing_email());
            $duplicateWindowSetting = new AnetAPI\SettingType();
            $duplicateWindowSetting->setSettingName("duplicateWindow");
            $duplicateWindowSetting->setSettingValue(($advanced_settings->get_option('duplicate_window')*60));
            $transactionRequestType = new AnetAPI\TransactionRequestType();
            $transactionRequestType->setTransactionType("refundTransaction");
            $transactionRequestType->setOrder($order);
            $transactionRequestType->setBillTo($customerAddressBilling);
            $transactionRequestType->setShipTo($customerAddressShipping);
            $transactionRequestType->setCustomer($customerData);
            $transactionRequestType->setAmount($amount);
            $transactionRequestType->setPayment($paymentOne);
            $transactionRequestType->setRefTransId($refTransId);
            $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refId);
            $request->setTransactionRequest($transactionRequestType);
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse($this->getEnvironment());
            if ($response != null) {
                if ("Ok" === $response->getMessages()->getResultCode()) {
                    $tresponse = $response->getTransactionResponse();
                    if ($tresponse != null && $tresponse->getMessages() != null) {
                        $return['state'] = 1;
                        $return['data'] = 'Transaction refunded,Amount:' . $amount . ' <a type="button" href="#" id="transaction-' . $tresponse->getTransId() . '" data-id="' . $tresponse->getTransId() . '"  class="button generate-items order-details-ref-trans">View Auth.net transaction status</a>';
                    } else {
                        if ($tresponse->getErrors() != null) {
                            $return['data'] = $tresponse->getErrors() [0]->getErrorCode() . ":" . $tresponse->getErrors() [0]->getErrorText() . "\n";
                        }
                    }
                } else {
                    $tresponse = $response->getTransactionResponse();
                    if ($tresponse != null && $tresponse->getErrors() != null) {
                        $return['data'] = $tresponse->getErrors() [0]->getErrorCode() . ":" . $tresponse->getErrors() [0]->getErrorText();
                    } else {
                        $return['data'] = $response->getMessages()->getMessage() [0]->getCode() . ":" . $response->getMessages()->getMessage() [0]->getText();
                    }
                }
            } else {
                $return['data'] = "No response returned";
            }
        }
        return $return;
    }

    /**
     * chargeCustomerProfile
     *
     * @param [type] $customer_order
     * @param [type] $tokenId
     * @return void
     */
    public function chargeCustomerProfile($customer_order, $tokenId) {
        $response = null;
        $token = WC_Payment_Tokens::get($tokenId);
        $payment_token = $token->get_token();
        $customerProfileId = $token->get_customer_profile_id();
        $merchantAuthentication = $this->getAuthAuthentication();
        if ($merchantAuthentication) {
            $profileToCharge = new AnetAPI\CustomerProfilePaymentType();
            $profileToCharge->setCustomerProfileId($customerProfileId);
            $paymentProfile = new AnetAPI\PaymentProfileType();
            $paymentProfile->setPaymentProfileId($payment_token);
            $profileToCharge->setPaymentProfile($paymentProfile);
            $response = $this->prepareResponse($customer_order, null, 'authCaptureTransaction', null, $profileToCharge);
        }
        return $response;
    }

    /**
     * prepareResponse
     *
     * @param [type] $customer_order
     * @param [type] $paymentType
     * @param [type] $ttype
     * @param [type] $reftrans
     * @param [type] $customer_payment_profile
     * @return void
     */
    private function prepareResponse($customer_order, $paymentType, $ttype, $reftrans = null, $customer_payment_profile = null) {
        $merchantAuthentication = $this->getAuthAuthentication();
        $advanced_settings = BS_Anet_WC_settings::getInstance();
        if ($merchantAuthentication) {
            $refId = 'ref' . time();
            $items = $customer_order->get_items();
            $order = new AnetAPI\OrderType();
            $order->setInvoiceNumber($customer_order->id);
            $orderDesc = array();
            foreach ($items as $item) {
                $orderDesc[] = $item->get_product_id();
            }
            $order->setDescription(implode(",", $orderDesc));
            $countryBilling = WC()->countries->countries[$customer_order->get_billing_country() ];
            $stateBilling = WC()->countries->get_states($customer_order->get_billing_country()) [$customer_order->get_billing_state() ];
            $customerAddressBilling = new AnetAPI\CustomerAddressType();
            $customerAddressBilling->setFirstName($customer_order->get_billing_first_name());
            $customerAddressBilling->setLastName($customer_order->get_billing_last_name());
            $customerAddressBilling->setCompany($customer_order->get_billing_company());
            $customerAddressBilling->setAddress($customer_order->get_billing_address_1() . '' . $customer_order->get_billing_address_2());
            $customerAddressBilling->setCity($customer_order->get_billing_city());
            $customerAddressBilling->setState($stateBilling);
            $customerAddressBilling->setZip($customer_order->get_billing_postcode());
            $customerAddressBilling->setCountry($countryBilling);
            $countryShipping = WC()->countries->countries[$customer_order->get_shipping_country() ];
            $stateShipping = WC()->countries->get_states($customer_order->get_shipping_country()) [$customer_order->get_shipping_state() ];
            $customerAddressShipping = new AnetAPI\CustomerAddressType();
            $customerAddressShipping->setFirstName($customer_order->get_shipping_first_name());
            $customerAddressShipping->setLastName($customer_order->get_shipping_last_name());
            $customerAddressShipping->setCompany($customer_order->get_shipping_company());
            $customerAddressShipping->setAddress($customer_order->get_shipping_address_1() . '' . $customer_order->get_shipping_address_2());
            $customerAddressShipping->setCity($customer_order->get_shipping_city());
            $customerAddressShipping->setState($stateShipping);
            $customerAddressShipping->setZip($customer_order->get_shipping_postcode());
            $customerAddressShipping->setCountry($countryShipping);
            $customerData = new AnetAPI\CustomerDataType();
            $customerData->setType("individual");
            $customerData->setId($customer_order->get_customer_id());
            $customerData->setEmail($customer_order->get_billing_email());
            $duplicateWindowSetting = new AnetAPI\SettingType();
            $duplicateWindowSetting->setSettingName("duplicateWindow");
            $duplicateWindowSetting->setSettingValue(($advanced_settings->get_option('duplicate_window')*60));
            $merchantDefinedField1 = new AnetAPI\UserFieldType();
            $merchantDefinedField1->setName("customerLoyaltyNum");
            $merchantDefinedField1->setValue($customer_order->get_order_key());
            $merchantDefinedField2 = new AnetAPI\UserFieldType();
            $merchantDefinedField2->setName("note");
            $merchantDefinedField2->setValue($customer_order->get_customer_note());
            $transactionRequestType = new AnetAPI\TransactionRequestType();

            if(!empty(get_woocommerce_currency()))
            {
            $transactionRequestType->setCurrencyCode(get_woocommerce_currency());  
            $merchantDefinedField3 = new AnetAPI\UserFieldType();   
            $merchantDefinedField3->setName("order_currency");
            $merchantDefinedField3->setValue(get_woocommerce_currency());
            }


            $transactionRequestType->setTransactionType($ttype);
            $transactionRequestType->setAmount($customer_order->get_total());
            if ($reftrans) $transactionRequestType->setRefTransId($reftrans);
            if ($paymentType) $transactionRequestType->setPayment($paymentType);
            if ($customer_payment_profile) $transactionRequestType->setProfile($customer_payment_profile);
            $transactionRequestType->setOrder($order);
            if (!$customer_payment_profile) $transactionRequestType->setBillTo($customerAddressBilling);
            $transactionRequestType->setShipTo($customerAddressShipping);
            $transactionRequestType->setCustomer($customerData);
            $transactionRequestType->addToTransactionSettings($duplicateWindowSetting);
            $transactionRequestType->addToUserFields($merchantDefinedField1);
            $transactionRequestType->addToUserFields($merchantDefinedField2);

            $request = new AnetAPI\CreateTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setRefId($refId);
            $request->setTransactionRequest($transactionRequestType);
            $controller = new AnetController\CreateTransactionController($request);
            $response = $controller->executeWithApiResponse($this->getEnvironment());
        } else {
            $response = null;
        }
        return $response;
    }
}
?>