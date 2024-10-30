<?php
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * BS_Anet_WC_payment
 * @author Bipin
 */
class BS_Anet_WC_payment {
    private static $instance;
    public $helper;
    public $settings;
    private $merchant_details = null;

    /**
     * getInstance
     *
     * @return void
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BS_Anet_WC_payment();
        }
        return self::$instance;
    }
    
    /**
     * __construct
     */
    public function __construct() {
        $this->helper = BS_Anet_WC_helper::getInstance();
        $this->settings = BS_Anet_WC_settings::getInstance();
    }

    /**
     * createTransaction
     *
     * @param [type] $orderid
     * @param [type] $type
     * @return void
     */
    public function createTransaction($orderid, $type) {
        $saved = isExistingMethod($type);
        $t = $saved ? 'S' : $type;
        $transaction = null;
        if (hasToAddNewMethod($type)) {
            $savedMethod = $this->add_payment_method($type, true);
            if ('failure' === $savedMethod['state']) {
                $response = new stdClass();
                $response->save_method_error = $savedMethod['data'];
                return $response;
            }
        }
        global $woocommerce;
        $customer_order = new WC_Order($orderid);
        switch ($t) {
            case 'C':
                update_post_meta($customer_order->get_id(), '_wc_anet_pm', 'card');
                $transaction = $this->helper->createCardTransaction($customer_order);
            break;
            case 'E':
                update_post_meta($customer_order->get_id(), '_wc_anet_pm', 'echeck');
                $transaction = $this->helper->createEcheckTransaction($customer_order);
            break;
            case 'P':
                update_post_meta($customer_order->get_id(), '_wc_anet_pm', 'paypal');
                $transaction = $this->helper->createpaypalTransaction($customer_order);
            break;
            case 'S':
                update_post_meta($customer_order->get_id(), '_wc_anet_pm', 'saved_token_' . $type);
                $transaction = $this->helper->chargeCustomerProfile($customer_order, $saved);
            break;
        }
        
        return $transaction;
    }

    /**
     * wc_response
     *
     * @param [type] $response
     * @param [type] $customer_order
     * @param [type] $return_url
     * @return void
     */
    public function wc_response($response, $customer_order, $return_url) {
        if ($response != null && !isset($response->paypal) && !isset($response->save_method_error)) {
            //not a paypal transaction
            if ("Ok" === $response->getMessages()->getResultCode()) {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getMessages() != null) {
                    //check if save method
                    global $woocommerce;
                    $customer_order->add_order_note(__('Transaction id:' . $tresponse->getTransId() . ',Auth Code:' . $tresponse->getAuthCode() . ',Description:' . $tresponse->getMessages() [0]->getDescription(), 'bs_anet_wc'));
                    $order_status = str_replace('wc-', '', $this->settings->get_option('default_ostatus'));
                    $customer_order->set_transaction_id($tresponse->getTransId());
                    $customer_order->update_status($order_status, 'Awaiting transaction settlement');
                    $woocommerce->cart->empty_cart();
                    $customer_order->reduce_order_stock();
                    return array('result' => 'success', 'redirect' => $return_url,);
                } else {
                    if ($tresponse->getErrors() != null) {
                        $note = $tresponse->getErrors() [0]->getErrorCode() . ':' . $tresponse->getErrors() [0]->getErrorText();
                        wc_add_notice($note, 'error');
                        $customer_order->add_order_note($note);
                    }
                }
            } else {
                $tresponse = $response->getTransactionResponse();
                if ($tresponse != null && $tresponse->getErrors() != null) {
                    $note = $tresponse->getErrors() [0]->getErrorCode() . ':' . $tresponse->getErrors() [0]->getErrorText();
                    wc_add_notice($note, 'error');
                    $customer_order->add_order_note($note);
                } else {
                    $note = $response->getMessages()->getMessage() [0]->getCode() . ':' . $response->getMessages()->getMessage() [0]->getText();
                    wc_add_notice($note, 'error');
                    $customer_order->add_order_note($note);
                }
            }
        } elseif ($response != null && isset($response->paypal) && !isset($response->save_method_error)) {
            //paypal transaction
            if (!$response->error) {
                if ($response->respcode == 5) {
                    return array('result' => 'success', 'redirect' => $response->sau,);
                } elseif ($response->respcode == 1) {
                    global $woocommerce;
                    $customer_order->add_order_note('Authorize.net complete payment.Transaction id:' . $response->tid . ',Auth Code:' . $response->code . ',Description:' . $response->desc);
                    $customer_order->payment_complete($tresponse->getTransId());
                    $woocommerce->cart->empty_cart();
                    $customer_order->reduce_order_stock();
                    return array('result' => 'success', 'redirect' => $return_url,);
                } else if ($response->respcode == 2) {
                    $note = 'Transaction Declined:' . $response->desc;
                    wc_add_notice($note, 'error');
                    $customer_order->add_order_note($note);
                } else {
                    $note = 'Transaction Error:' . $response->desc;
                    wc_add_notice($note, 'error');
                    $customer_order->add_order_note($note);
                }
            } else {
                $note = $response->error;
                wc_add_notice($note, 'error');
                $customer_order->add_order_note($note);
            }
        } elseif ($response != null && !isset($response->paypal) && isset($response->save_method_error)) {
            wc_add_notice($response->save_method_error, 'error');
        } else {
            wc_add_notice('No response returned from gateway.', 'error');
        }
    }

    /**
     * createRefundTransaction
     *
     * @param [type] $order_id
     * @param [type] $amount
     * @param [type] $reason
     * @return void
     */
    public function createRefundTransaction($order_id, $amount, $reason) {
        $customer_order = new WC_Order($order_id);
        $transaction_id = $customer_order->transaction_id;
        $return = false;
        $transactDetails = $this->getTransactionDetails($transaction_id);
        $ptype = $customer_order->get_meta('_wc_anet_pm');
       
        $response = null;
        if (($transactDetails != null) && ("Ok" === $transactDetails->getMessages()->getResultCode())) {
            $transaction = $transactDetails->getTransaction();
            switch ($ptype) {
                case 'card':
                    $response = $this->processCardRefund($transaction, $amount, $customer_order);
                break;
                case 'saved_token_C':
                    $response = $this->processCardRefund($transaction, $amount, $customer_order);
                break;
                case 'echeck' :
                    $response = $this->processEcheckRefund($transaction, $amount, $customer_order);
                break;
                case 'saved_token_E':
                    $response = $this->processEcheckRefund($transaction, $amount, $customer_order);
                break;
                case 'paypal':
                    $response = $this->processPaypalRefund($transaction, $amount, $customer_order);
                break;
            }
            if ($response['state']) {
                $customer_order->add_order_note($response['data']);
                return true;
            } else {
                return new WP_Error("RefundError", $response['data']);
            }
        } else {
            return new WP_Error("RefundError", 'Unable to process refund transaction.');
        }
    }

    /**
     * processPaypalRefund
     *
     * @param [type] $transaction
     * @param [type] $amount
     * @param [type] $customer_order
     * @return void
     */
    private function processPaypalRefund($transaction, $amount, $customer_order) {
        $return = array("state" => 0, "data" => null);
        $transaction_status = $transaction->getTransactionStatus();
        $transactionId = $transaction->getTransId();
        $transactionAmount = number_format((float)$transaction->getAuthAmount(), 2, '.', '');
        if ('capturedPendingSettlement' === $transaction_status && $amount == $transactionAmount) {
            //perform void only if refund amount  is equal to transaction amount.
            $payPalType = new AnetAPI\PayPalType();
            $payPalType->setSuccessUrl("");
            $payPalType->setCancelUrl("");
            $paymentType = new AnetAPI\PaymentType();
            $paymentType->setPayPal($payPalType);
            $return = $this->helper->performVoid($transactionId, $paymentType);
        } else {
            if ('settledSuccessfully' === $transaction_status) {
                $payPalType = new AnetAPI\PayPalType();
                $payPalType->setSuccessUrl(get_site_url() . "/wc-api/paypal_return_success?oid=" . $customer_order->id . '&cid=' . $customer_order->customer_id);
                $payPalType->setCancelUrl(get_site_url() . "/wc-api/paypal_return_cancelled?oid=" . $customer_order->id . '&cid=' . $customer_order->customer_id);
                $paymentOne = new AnetAPI\PaymentType();
                $paymentOne->setPayPal($payPalType);
                $return = $this->helper->performRefund($paymentOne, $transactionId, $amount, $customer_order);
            } else {
                $return['data'] = "Transaction with status " . $transaction_status . " cannot be refunded,You can consider voiding it.";
            }
        }
        return $return;
    }

    /**
     * processCardRefund
     *
     * @param [type] $transaction
     * @param [type] $amount
     * @param [type] $customer_order
     * @return void
     */
    private function processCardRefund($transaction, $amount, $customer_order) {
        $return = array('state' => 0, 'data' => null);
        $transaction_status = $transaction->getTransactionStatus();
        $transactionId = $transaction->getTransId();
        $transactionAmount = number_format((float)$transaction->getAuthAmount(), 2, '.', '');
        if ('capturedPendingSettlement' === $transaction_status && $amount == $transactionAmount) {
            $return = $this->helper->performVoid($transactionId);
        } else {
            if ('settledSuccessfully' === $transaction_status) {
                $Tpayment = $transaction->getPayment();
                $creditcard = $Tpayment->getCreditCard();
                $creditCard = new AnetAPI\CreditCardType();
                $creditCard->setCardNumber($creditcard->getCardNumber());
                $creditCard->setExpirationDate($creditcard->getExpirationDate());
                $paymentOne = new AnetAPI\PaymentType();
                $paymentOne->setCreditCard($creditCard);
                $return = $this->helper->performRefund($paymentOne, $transactionId, $amount, $customer_order);
            } else {
                $return['data'] = "Transaction with status " . $transaction_status . " cannot be refunded.";
            }
        }
        return $return;
    }

    /**
     * processEcheckRefund
     *
     * @param [type] $transaction
     * @param [type] $amount
     * @param [type] $customer_order
     * @return void
     */
    private function processEcheckRefund($transaction, $amount, $customer_order) {
        $return = array('state' => 0, 'data' => null);
        $transaction_status = $transaction->getTransactionStatus();
        $transactionId = $transaction->getTransId();
        $transactionAmount = number_format((float)$transaction->getAuthAmount(), 2, '.', '');
        if ('capturedPendingSettlement' === $transaction_status && $amount == $transactionAmount) {
            $return = $this->helper->performVoid($transactionId);
        } else {
            if ('settledSuccessfully' === $transaction_status) {
                $otb = $transaction->getPayment()->getBankAccount();
                $bankAccount = new AnetAPI\BankAccountType();
                $bankAccount->setRoutingNumber($otb->getRoutingNumber());
                $bankAccount->setAccountNumber($otb->getAccountNumber());
                $bankAccount->setNameOnAccount($otb->getNameOnAccount());
                $paymentOne = new AnetAPI\PaymentType();
                $paymentOne->setBankAccount($bankAccount);
                $return = $this->helper->performRefund($paymentOne, $transactionId, $amount, $customer_order);
            } else {
                $msg = 'voided' === $transaction_status ? 'Transaction is already voided.' : 'Only transactions with status settledSuccessfully can be refunded.Subjected transaction has status ' . $transaction_status . ".Full amount of transaction can still be refunded with automated void performed.";
                $return['data'] = $msg;
            }
        }
        return $return;
    }

    /**
     * getTransactionDetails
     *
     * @param [type] $transid
     * @return void
     */
    public function getTransactionDetails($transid) {
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication) {
            // Set the transaction's refId
            $refId = 'ref' . time();
            $request = new AnetAPI\GetTransactionDetailsRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setTransId($transid);
            $controller = new AnetController\GetTransactionDetailsController($request);
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
        } else {
            $response = null;
        }
        return $response;
    }

    /**
     * verifyAnet
     *
     * @param [type] $settings
     * @return void
     */
    public function verifyAnet($settings) {
        if (!$this->merchant_details) {
            $resp = array("state" => 0, 'data' => null);
            $environment = 'yes' === $settings['environment'] ? \net\authorize\api\constants\ANetEnvironment::SANDBOX : \net\authorize\api\constants\ANetEnvironment::PRODUCTION;
            $basePathPi = BS_ANET_WC_BASEURL_ASSETS.'/images/paymentmethods/';
            if (!empty($settings['api_login']) && !empty($settings['api_login'])) {
                $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
                $merchantAuthentication->setName($settings['api_login']);
                $merchantAuthentication->setTransactionKey($settings['trans_key']);
                // Set the transaction's refId
                $refId = 'ref' . time();
                $request = new AnetAPI\GetMerchantDetailsRequest();
                $request->setMerchantAuthentication($merchantAuthentication);
                $controller = new AnetController\GetMerchantDetailsController($request);
                $response = $controller->executeWithApiResponse($environment);
                if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                    $resp['state'] = 1;
                    $resp['data']['mode'] = $response->getIsTestMode();
                    $resp['data']['mname'] = $response->getMerchantName();
                    $resp['data']['gid'] = $response->getGatewayId();
                    $resp['data']['public_client_key'] = $response->getPublicClientKey();
                    foreach ($response->getProcessors() as $pk => $processor) {
                        $resp['data']['fields'][] = $processor->getName();
                    }
                    foreach ($response->getPaymentMethods() as $pm => $pmethods) {
                        $resp['data']['pmethods'][strtolower($pmethods) ] = "<img class='anet_pm_method' title='" . $pmethods . "' alt='" . $pmethods . "' src='" . $basePathPi . strtolower($pmethods) . ".svg' />";
                    }
                    foreach ($response->getCurrencies() as $cr => $currency) {
                        $resp['data']['currencies'][] = $currency;
                    }
                } else {
                    $emsg = $response->getMessages()->getMessage();
                    $resp['data'] = $emsg[0]->getCode() . ':' . $emsg[0]->getText();
                }
            } else {
                $resp['data'] = "Unable to verify merchant settings.Failed creating public client key";
            }
            $this->merchant_details = $resp;
            return $this->merchant_details;
        } else {
            return $this->merchant_details;
        }
    }


    /**
     * add_payment_method
     *
     * @param [type] $pmtype
     * @param boolean $return_response
     * @return void
     */
    public function add_payment_method($pmtype, $return_response = false) {
        $return = array('result' => 'failure');
        $customerhelper = BS_Anet_WC_customer::getInstance();
        $result = $customerhelper->createCustomerPaymentProfile(get_current_user_id(), $pmtype);
        if ('success' == $result['state']) {
            $tokenHelper = WC_Gateway_anet_Token_Helper::getinstance();
            $tokenHelper->saveToken($pmtype, $result['data']);
        }
        $return['result'] = $result['state'];
        return !$return_response ? $return : $result;
    }

    private function delete_order_meta($order)
    {

    }
}
