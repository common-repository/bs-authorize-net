<?php
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;

/**
 * BS_Anet_WC_Reports class
 *  @author Bipin
 */
class BS_Anet_WC_Reports {
   
    private static $instance;
    var $me = null;
    var $merchantInfo = null;
    var $helper = null;

    /**
     * getInstance
     *
     * @return void
     */
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BS_Anet_WC_Reports();
        }
        return self::$instance;
    }

    /**
     * __construct
     */
    private function __construct() {
        $this->helper = BS_Anet_WC_helper::getInstance();
    }

    /**
     * initReports
     *
     * @return void
     */
    public function initReports() {
        add_action('admin_menu', array($this, 'merchant_dashboard_menu'));
    }

    /**
     * merchant_dashboard_menu
     *
     * @return void
     */
    public function merchant_dashboard_menu() {
        $parent_slug = 'woocommerce';
        $page_title = 'My merchant dashboard';
        $menu_title = 'Merchant Dashboard';
        $capability = 'manage_options';
        $menu_slug = 'merchantdashboard';
        $function = array($this, 'anet_reports');
        $icon_url = 'dashicons-media-code';
        add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $function, $icon_url);
        add_action('admin_enqueue_scripts', 'loadBackendScripts');
    }

    /**
     * anet_reports
     *
     * @return void
     */
    public function anet_reports() {
        require_once (BS_ANET_WC_ABSPATH . 'reports' . DIRECTORY_SEPARATOR . 'reports_anet_admin.php');
    }
   
    /**
     * getTransactionsbatchList
     *
     * @return void
     */
    public function getTransactionsbatchList() {
        $result = array("state" => 0, "data" => null);
        if (isset($_POST['batchrangev'])) {
            $batchRange = wc_clean($_POST['batchrangev']);
            $batchRange = explode('*', $batchRange);
            $startDate = datetime::createfromformat('Y-m-d', $batchRange[0]);
            $endDate = datetime::createfromformat('Y-m-d', $batchRange[1]);
            $merchantAuthentication = $this->helper->getAuthAuthentication();
            if ($merchantAuthentication && $startDate && $endDate) {
                $refId = 'ref' . time();
                $request = new AnetAPI\GetSettledBatchListRequest();
                $request->setMerchantAuthentication($merchantAuthentication);
                $request->setIncludeStatistics(true);
                $request->setFirstSettlementDate($startDate);
                $request->setLastSettlementDate($endDate);
                $controller = new AnetController\GetSettledBatchListController($request);
                $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
                if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                    if (count($response->getBatchList())) {
                        $result['state'] = 1;
                        $bdata = array();
                        foreach ($response->getBatchList() as $batch) {
                            $bdata['batch_id'] = $batch->getBatchId();
                            $bdata['sutc'] = $batch->getSettlementTimeUTC()->format('r');
                            $bdata['bsloc'] = $batch->getSettlementTimeLocal()->format('D, d M Y H:i:s');
                            $bdata['state'] = $batch->getSettlementState();
                            $bdta['mtype'] = $batch->getMarketType();
                            $bdata['batch_product'] = $batch->getProduct();
                            $bdata['stats'] = array();
                            $bdata['total'] = 0;
                            $bdata['refunds'] = 0;
                            $bdata['cattempts'] = 0;
                            $bdata['rattempts'] = 0;
                            $bdata['declines'] = 0;
                            $bdata['voids'] = 0;
                            $bdata['errors'] = 0;
                            foreach ($batch->getStatistics() as $statistics) {
                                $type = strtolower($statistics->getAccountType());
                                $type = $type ? $type : 'paypal';
                                $bdata['stats'][$type] = array();
                                $bdata['stats'][$type]['accountType'] = $statistics->getAccountType() ? $statistics->getAccountType() : 'Paypal';
                                $bdata['stats'][$type]['chargeAmount'] = $statistics->getChargeAmount();
                                $bdata['stats'][$type]['chargeCount'] = $statistics->getChargeCount();
                                $bdata['stats'][$type]['refundAmount'] = $statistics->getRefundAmount();
                                $bdata['stats'][$type]['refundCount'] = $statistics->getRefundCount();
                                $bdata['stats'][$type]['voidCount'] = $statistics->getVoidCount();
                                $bdata['stats'][$type]['declineCount'] = $statistics->getDeclineCount();
                                $bdata['stats'][$type]['errorCount'] = $statistics->getErrorCount();
                                $bdata['total']+= (float)$statistics->getChargeAmount();
                                $bdata['refunds']+= (float)$statistics->getRefundAmount();
                                $bdata['cattempts']+= $statistics->getChargeCount();
                                $bdata['rattempts']+= $statistics->getRefundCount();
                                $bdata['declines']+= $statistics->getDeclineCount();;
                                $bdata['voids']+= $statistics->getVoidCount();
                                $bdata['errors']+= $statistics->getErrorCount();
                            }
                            $result['data'][] = $bdata;
                        }
                        $result['merchantInfo'] = $this->helper->getMerchantinfo()->getPaymentMethods();
                        $result['currency'] = $this->helper->getMerchantinfo()->getCurrencies() [0];
                    } else {
                        $result['data'] = 'No records found in the given date range.';
                    }
                } else {
                    if ($response):
                        $emessage = $response->getMessages()->getMessage();
                        $result['data'] = $emessage[0]->getCode() . " : " . $emessage[0]->getText();
                    else:
                        $result['data'] = "Unable to connect to remote server.";
                    endif;
                }
            }
        }
        return $result;
    }
    
    /**
     * anet_setteled_transactions
     *
     * @return void
     */
    public function anet_setteled_transactions() {
        $return = array('state' => 0, 'data' => null);
        $batchId = isset($_GET['batch_id']) ? (int)wc_clean($_GET['batch_id']) : 0;
        $limit = isset($_GET['limit']) ? (int)wc_clean($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? (int)wc_clean($_GET['offset']) : 1;
        $ajax = isset($_GET['remote']) ? (int)wc_clean($_GET['remote']) : 0;
        
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication && $batchId && $offset && $limit) {
            $refId = 'ref' . time();
            $request = new AnetAPI\GetTransactionListRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setBatchId($batchId);
            $paging = new AnetAPI\PagingType();
            $paging->setLimit($limit);
            $paging->setOffset($offset);
            $request->setPaging($paging);
            $controller = new AnetController\GetTransactionListController($request);
            //Retrieving transaction list for the given Batch Id
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            $total = $response->getTotalNumInResultSet();
            if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                if ($response->getTransactions() == null) {
                    $return['data'] = "No Transaction to display in this Batch.";
                } else {
                    $return['state'] = 1;
                    $return['total'] = $total;
                    $return['offset'] = $offset;
                    $return['limit'] = $limit;
                    foreach ($response->getTransactions() as $transaction) {
                        $transdata = array();
                        $transdata['id'] = $transaction->getTransId();
                        $transdata['submittedOnU'] = date_format($transaction->getSubmitTimeUTC(), 'd M Y g:i:s A');
                        $transdata['submittedOnL'] = date_format($transaction->getSubmitTimeLocal(), 'd M Y g:i:s A');
                        $transdata['status'] = $transaction->getTransactionStatus();
                        $transdata['name'] = $transaction->getFirstName() . ' ' . $transaction->getLastName();
                        $transdata['account_type'] = $transaction->getAccountType() ? $transaction->getAccountType() : 'Paypal';
                        $transdata['amount'] = number_format($transaction->getSettleAmount(), 2, '.', '');
                        $return['data'][] = $transdata;
                    }
                }
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                $return['data'] = $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText();
            }
        }
        if (!$ajax) {
            require_once (BS_ANET_WC_ABSPATH . 'reports' . DIRECTORY_SEPARATOR . 'reports_anet_reports.php');
        } else {
            echo json_encode($return);
        }
        wp_die();
    }
    
    /**
     * anet_unsetteled_transactions
     *
     * @return void
     */
    public function anet_unsetteled_transactions() {
        $return = array('state' => 0, 'data' => null);
        $limit = isset($_GET['limit']) ? (int)wc_clean($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? (int)wc_clean($_GET['offset']) : 1;
        $ajax = isset($_GET['remote']) ? (int)wc_clean($_GET['remote']) : 0;
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication && $offset && $limit) {
            $refId = 'ref' . time();
            $request = new AnetAPI\GetUnsettledTransactionListRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $paging = new AnetAPI\PagingType();
            $paging->setLimit($limit);
            $paging->setOffset($offset);
            $request->setPaging($paging);
            $controller = new AnetController\GetUnsettledTransactionListController($request);
            //Retrieving transaction list for the given Batch Id
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            $total = $response->getTotalNumInResultSet();
            if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                if ($response->getTransactions() == null) {
                    $return['data'] = "There are no transactions in unsettled state.";
                } else {
                    $return['state'] = 1;
                    $return['total'] = $total;
                    $return['offset'] = $offset;
                    $return['limit'] = $limit;
                    foreach ($response->getTransactions() as $transaction) {
                        //do not display expired or general error transactions
                        if (!in_array($transaction->getTransactionStatus(), array('generalError', 'expired'))) {
                            $transdata = array();
                            $transdata['id'] = $transaction->getTransId();
                            $transdata['submittedOnU'] = date_format($transaction->getSubmitTimeUTC(), 'd M Y g:i:s A');
                            $transdata['submittedOnL'] = date_format($transaction->getSubmitTimeLocal(), 'd M Y g:i:s A');
                            $transdata['status'] = $transaction->getTransactionStatus();
                            $transdata['name'] = $transaction->getFirstName() . ' ' . $transaction->getLastName();
                            $transdata['account_type'] = $transaction->getAccountType() ? $transaction->getAccountType() : 'Paypal';
                            $transdata['amount'] = number_format($transaction->getSettleAmount(), 2, '.', '');
                            $return['data'][] = $transdata;
                        }
                    }
                }
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                $return['data'] = $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText();
            }
        }
        if (!$ajax) {
            return $return;
        } else {
            echo json_encode($return);
        }
        wp_die();
    }
    
    /**
     * anet_held_transactions
     *
     * @return void
     */
    public function anet_held_transactions() {
        $return = array('state' => 0, 'data' => null);
        $limit = isset($_GET['limit']) ? (int)wc_clean($_GET['limit']) : 50;
        $offset = isset($_GET['offset']) ? (int)wc_clean($_GET['offset']) : 1;
        $ajax = isset($_GET['remote']) ? (int)wc_clean($_GET['remote']) : 0;
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication && $offset && $limit) {
            $refId = 'ref' . time();
            $request = new AnetAPI\GetUnsettledTransactionListRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setStatus("pendingApproval");
            $paging = new AnetAPI\PagingType();
            $paging->setLimit($limit);
            $paging->setOffset($offset);
            $request->setPaging($paging);
            $controller = new AnetController\GetUnsettledTransactionListController($request);
            //Retrieving transaction list for the given Batch Id
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            $total = $response->getTotalNumInResultSet();
            if (($response != null) && ("Ok" === $response->getMessages()->getResultCode())) {
                if ($response->getTransactions() == null) {
                    $return['data'] = "No suspicious transactions for the merchant.";
                } else {
                    $return['state'] = 1;
                    $return['total'] = $total;
                    $return['offset'] = $offset;
                    $return['limit'] = $limit;
                    foreach ($response->getTransactions() as $transaction) {
                        $transdata = array();
                        $transdata['id'] = $transaction->getTransId();
                        $transdata['submittedOnU'] = date_format($transaction->getSubmitTimeUTC(), 'd M Y g:i:s A');
                        $transdata['submittedOnL'] = date_format($transaction->getSubmitTimeLocal(), 'd M Y g:i:s A');
                        $transdata['status'] = $transaction->getTransactionStatus();
                        $transdata['name'] = $transaction->getFirstName() . ' ' . $transaction->getLastName();
                        $transdata['account_type'] = $transaction->getAccountType();
                        $transdata['amount'] = number_format($transaction->getSettleAmount(), 2, '.', '');
                        $return['data'][] = $transdata;
                    }
                }
            } else {
                $errorMessages = $response->getMessages()->getMessage();
                $return['data'] = $errorMessages[0]->getCode() . "  " . $errorMessages[0]->getText();
            }
        }
        if (!$ajax) {
            return $return;
        } else {
            echo json_encode($return);
        }
        wp_die();
    }

    /**
     * anet_transaction_details
     *
     * @return void
     */
    public function anet_transaction_details() {
        $return = array('state' => 0, 'data' => '');
        $tid = isset($_GET['transaction_id']) ? (int)wc_clean($_GET['transaction_id']) : 0;
        $ajax = isset($_GET['remote']) ? (int)wc_clean($_GET['remote']) : 0;
        $type = isset($_GET['type']) ? wc_clean($_GET['type']) : 'credit';
        $payment = BS_Anet_WC_payment::getInstance();
        $transaction = $payment->getTransactionDetails($tid);
        if (($transaction != null) && ("Ok" === $transaction->getMessages()->getResultCode())) {
            $return['state'] = 1;
            $transdata = $this->helper->processTransactionData($transaction);
            $return['data'] = $transdata;
        } else {
            $return['data'] = "Unable to locate transaction.";
        }
        if (!$ajax) {
            require_once (BS_ANET_WC_ABSPATH . 'reports' . DIRECTORY_SEPARATOR . 'reports_anet_transaction.php');
        } elseif (!$ajax && $type == 'credit') {
            require_once (BS_ANET_WC_ABSPATH . 'reports' . DIRECTORY_SEPARATOR . 'reports_anet_transaction_refund.php');
        } else {
            echo json_encode($return);
        }
        wp_die();
    }

    /**
     * anet_transaction_void
     *
     * @return void
     */
    public function anet_transaction_void() {
        $tid = isset($_GET['tid']) ? (int)wc_clean($_GET['tid']) : 0;
        $order_id = isset($_GET['o']) ? (int)wc_clean($_GET['o']) : false;
        $moc = isset($_GET['moc']) ? (int)wc_clean($_GET['moc']) : false;
        $return = array('state' => 0, 'data' => null);
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication && $tid && check_ajax_referer(ANET_AJAX_NONCE, 'nonce')) {
            $processVoid = false;
            $processWCorder = false;
            $customer_order = null;
            if ($moc && $order_id) {
                global $woocommerce;
                $wc_order = wc_get_order($order_id);
                if ($wc_order) $customer_order = $wc_order;
            }
            if ($moc && !$customer_order) {
                $return['data'] = 'Woocomerce order not found.';
            } elseif ($moc && $customer_order) {
                $processVoid = true;
                $processWCorder = true;
            } elseif (!$moc) {
                $processVoid = true;
            }
            if ($processVoid) {
                $refId = 'ref' . time();
                //create a transaction
                $transactionRequestType = new AnetAPI\TransactionRequestType();
                $transactionRequestType->setTransactionType("voidTransaction");
                $transactionRequestType->setRefTransId($tid);
                $request = new AnetAPI\CreateTransactionRequest();
                $request->setMerchantAuthentication($merchantAuthentication);
                $request->setRefId($refId);
                $request->setTransactionRequest($transactionRequestType);
                $controller = new AnetController\CreateTransactionController($request);
                $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
                if ($response != null) {
                    if ("Ok" === $response->getMessages()->getResultCode()) {
                        $tresponse = $response->getTransactionResponse();
                        if ($tresponse != null && $tresponse->getMessages() != null) {
                            $rep = array('transaction_resp_code' => $tresponse->getResponseCode(), 'void_transaction_success_auth_code' => $tresponse->getAuthCode(), 'void_transaction_success_trans_id' => $tresponse->getTransId(), 'code' => $tresponse->getMessages() [0]->getCode(), 'description' => $tresponse->getMessages() [0]->getDescription());
                            if ($processWCorder) $customer_order->update_status("refunded", 'Transaction voided: Void transaction id = ' . $tresponse->getTransId() . ',Description=' . $tresponse->getMessages() [0]->getDescription());
                            $return['state'] = 1;
                            $return['data'] = $rep;
                        } else {
                            if ($tresponse->getErrors() != null) {
                                $return['data'] = $tresponse->getErrors() [0]->getErrorCode() . ":" . $tresponse->getErrors() [0]->getErrorText();
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
        } else {
            $return['data'] = "Unknown error occured please try again.";
        }
        echo json_encode($return);
        wp_die();
    }
    
  
    
    /**
     * anet_transaction_fdsaction
     *
     * @return void
     */
    public function anet_transaction_fdsaction() {
        $return = array('state' => 0, 'data' => 'Sorry,something went wrong.');
        $fds = isset($_GET['fds']) ? sanitize_text_field($_GET['fds']) : false;
        $tid = isset($_GET['transaction_id']) ? sanitize_text_field($_GET['transaction_id']) : false;
        $merchantAuthentication = $this->helper->getAuthAuthentication();
        if ($merchantAuthentication && $fds && $tid) {
            $action = 'A' === $fds ? 'approve' : 'decline';
            $refId = 'ref' . time();
            $transactionRequestType = new AnetAPI\HeldTransactionRequestType();
            $transactionRequestType->setAction($action);
            $transactionRequestType->setRefTransId($tid);
            $request = new AnetAPI\UpdateHeldTransactionRequest();
            $request->setMerchantAuthentication($merchantAuthentication);
            $request->setHeldTransactionRequest($transactionRequestType);
            $controller = new AnetController\UpdateHeldTransactionController($request);
            $response = $controller->executeWithApiResponse($this->helper->getEnvironment());
            if ($response != null) {
                if ("Ok" === $response->getMessages()->getResultCode()) {
                    $tresponse = $response->getTransactionResponse();
                    if ($tresponse != null && $tresponse->getMessages() != null) {
                        $return['state'] = 1;
                        $rep = array();
                        $rep['resp_code'] = $tresponse->getResponseCode();
                        $rep['auth_code'] = $tresponse->getAuthCode();
                        $rep['trans_id'] = $tresponse->getTransId();
                        $rep['code'] = $tresponse->getMessages() [0]->getCode();
                        $rep['description'] = 'Transaction id ' . $rep['trans_id'] . ':' . $tresponse->getMessages() [0]->getDescription();
                        $rep['action'] = $action = $fds == 'A' ? 'approved' : 'declined';
                        $return['data'] = $rep;
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
        echo json_encode($return);
        wp_die();
    }

    /**
     * view_anet_transaction
     *
     * @param [type] $order
     * @return void
     */
    public function view_anet_transaction($order) {
        if ($order->get_transaction_id() && in_array($order->get_payment_method(), array(BS_ANET_WC_CC_PLUGIN_ID, BS_ANET_WC_ECHECK_PLUGIN_ID, BS_ANET_WC_PAYPAL_PLUGIN_ID))) {
            echo '<a type="button" id="wc-order-authnet-trans-details" href="#" style="margin:15px auto;" data-id="' . $order->get_transaction_id() . '" rel="modal:open" aria-disabled="false" class="button button-primary btn-transact-detail">' . __('View Authorize.net customer checkout transaction', 'gatewaytrans') . '</a>';
        }
    }
}
?>