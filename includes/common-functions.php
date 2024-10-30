<?php
/**
 * loadBackendScripts
 * @author Bipin
 * @param [type] $hook_suffix
 * @return void
 */
function loadBackendScripts($hook_suffix) {
 
    if ('post.php' === $hook_suffix || 'woocommerce_page_merchantdashboard' === $hook_suffix) {
        $basePathCss = BS_ANET_WC_BASEURL_ASSETS . 'css/';
        $basePathScr = BS_ANET_WC_BASEURL_ASSETS . 'js/';
        $data = array('startdate' => date('m/d/Y', strtotime(date('m/d/Y') . ' -29 day')), 'enddate' => date('m/d/Y'), 'admin_url' => admin_url('admin-ajax.php'), 'ajax_nonce' => wp_create_nonce(ANET_AJAX_NONCE));
        if (isset($_POST['batchrangev'])) {
            $batchRange = sanitize_text_field($_POST['batchrangev']);
            $batchRange = explode('*', $batchRange);
            $startDate = datetime::createfromformat('Y-m-d', $batchRange[0]);
            $endDate = datetime::createfromformat('Y-m-d', $batchRange[1]);
            $data['startdate'] = $startDate->format('m/d/Y');
            $data['enddate'] = $endDate->format('m/d/Y');
        }
        wp_enqueue_script('momment', $basePathScr . 'moment.min.js', array('jquery'));
        wp_enqueue_script('jquery-blockui');
        wp_enqueue_script('confirm', $basePathScr.'jquery-confirm.min.js');
        wp_enqueue_style('confirm', $basePathCss.'jquery-confirm.min.css');
        wp_enqueue_style('tooltipster', $basePathCss.'tooltipster.bundle.min.css');
        wp_enqueue_script('tooltipster', $basePathScr.'tooltipster.bundle.min.js');
        wp_enqueue_script('daterangepicker', $basePathScr . 'daterangepicker.js');

        wp_enqueue_script('chartjs', $basePathScr . 'Chart.bundle.min.js');
        wp_enqueue_style('chartcss', $basePathCss.'Chart.min.css');

        wp_enqueue_script('smodal', $basePathScr . 'jquery.modal.js');
        wp_enqueue_style('animate', $basePathCss.'animate.min.css');
        wp_enqueue_style('smodal', $basePathCss.'jquery.modal.min.css');

        wp_enqueue_style('fontawesome-anet', $basePathCss.'icons/style.css');
        wp_enqueue_style('daterangepicker', $basePathCss . 'daterangepicker.css');
        wp_enqueue_script('authorize', $basePathScr . 'authorize.js');
        wp_localize_script('authorize', 'batchrange', $data);
        wp_enqueue_style('authorize', $basePathCss . 'authorize.css');
   }
}

/**
 * load_frontend_scripts
 *
 * @return void
 */
function load_frontend_scripts() {
    $helper = BS_Anet_WC_helper::getInstance();
    $params = $helper->getParams();
    $basePathCss = BS_ANET_WC_BASEURL_ASSETS . 'css/';
    $basePathScr = BS_ANET_WC_BASEURL_ASSETS . 'js/';
    if (!$params) {
        return;
    }
    if (!is_cart() && !is_checkout() && !isset($_GET['pay_for_order'])) {
        return;
    }
    $key = $params->public_client_key;
    $acceptJs = $params->environment == 'yes' ? 'https://jstest.authorize.net/v1/Accept.js' : 'https://js.authorize.net/v1/Accept.js';
    $login = $params->api_login;
    
    if ($key && wc_checkout_is_https()):
        wp_enqueue_script('acceptjs', $acceptJs, array(), null, false);
        add_filter('script_loader_tag', 'async_scripts', 10, 3);
        wp_enqueue_script('authorize-frontend-helper', $basePathScr . 'authorize-frontend-helper.js', array('jquery'));
        $data = array("k" => $key, "l" => $login, 'echeck' => BS_ANET_WC_ECHECK_PLUGIN_ID, 'cc' => BS_ANET_WC_CC_PLUGIN_ID, 'nonce' => ANET_AJAX_NONCE, 'ajaxpath' => admin_url('admin-ajax.php'));
        wp_localize_script('authorize-frontend-helper', 'paramsgw', $data);
    endif;
}

/**
 * async_scripts
 *
 * @param [type] $tag
 * @param [type] $handle
 * @param [type] $src
 * @return void
 */
function async_scripts($tag, $handle, $src) {
    $async_scripts = array('acceptjs');
    if (in_array($handle, $async_scripts)) {
        return '<script type="text/javascript" src="' . $src . '" charset="utf-8"> </script>';
    }
    return $tag;
}

/**
 * parse_request_echeck
 *
 * @return void
 */
function parse_request_echeck() {

    $return = array();
    $return['anet_ecroutingnumber'] = isset($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-routing-number']) && !empty(wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-routing-number'])) ? preg_replace('/\s+/', '', wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-routing-number'])) : null;
    $return['anet_ecaccnum'] = isset($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-number']) && !empty(wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-number'])) ? preg_replace('/\s+/', '', wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-number'])) : null;
    $return['anet_ecnoacc'] = isset($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-name']) && !empty(wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-name'])) ? wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-name']) : null;
    $return['anet_ecacctype'] = isset($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-type']) && !empty(wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-type'])) ? preg_replace('/\s+/', '', wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-account-type'])) : null;
    $return['anet_ecbankname'] = isset($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-bank-name']) && !empty(wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-bank-name'])) ? wc_clean($_POST[BS_ANET_WC_ECHECK_PLUGIN_ID . '-bank-name']) : null;

    return $return;
}

/**
 * parse_request_cc
 *
 * @return void
 */
function parse_request_cc() {
    $return = array();
    $return['cardnumber'] = isset($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-number']) && !empty(wc_clean($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-number'])) ? preg_replace('/\s+/', '', wc_clean($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-number'])) : null;
    $return['expDate'] = isset($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-expiry']) && !empty(wc_clean($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-expiry'])) ? preg_replace('/\s+/', '', wc_clean($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-expiry'])) : null;
    $return['cardExpiryMonth'] = isset($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-expiry']) && !empty(wc_clean($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-expiry'])) ? trim(explode("/", $return['expDate']) [0]) : null;
    $return['cardExpiryYear'] = isset($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-expiry']) && !empty(wc_clean($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-expiry'])) ? trim(explode("/", $return['expDate']) [1]) : null;
    $return['cvv'] = isset($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-cvc']) && !empty(wc_clean($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-cvc'])) ? preg_replace('/\s+/', '', wc_clean($_POST[BS_ANET_WC_CC_PLUGIN_ID . '-card-cvc'])) : null;
    //adjust year
    $return['cardExpiryYear'] = $return['cardExpiryMonth'] && strlen($return['cardExpiryYear']) == 2 ? substr(date("Y"), 0, 2) . $return['cardExpiryYear'] : $return['cardExpiryYear'];
    return $return;
}

/**
 * parse_request_opaquedata
 *
 * @return void
 */
function parse_request_opaquedata() {
    $return = array();
    $return['dataDescriptor'] = isset($_POST['dataDescriptor']) && !empty(wc_clean($_POST['dataDescriptor'])) ? preg_replace('/\s+/', '', wc_clean($_POST['dataDescriptor'])) : null;
    $return['dataValue'] = isset($_POST['dataValue']) && !empty(wc_clean($_POST['dataValue'])) ? preg_replace('/\s+/', '', wc_clean($_POST['dataValue'])) : null;
    return $return;
}

/**
 * printTransactionChart
 *
 * @param [type] $id
 * @param [type] $pms
 * @param [type] $batchData
 * @param [type] $currency
 * @param [type] $type
 * @return void
 */
function printTransactionChart($id, $pms, $batchData, $currency, $type) {
    $canvid = '"#' . $id . $batchData['batch_id'] . '"';
    $stats = $batchData['stats'];
    $legendColors = array("americanexpress" => "#006cc9", "discover" => "#ea6520", "echeck" => "#2eae4b", "jcb" => "#9a2032", "mastercard" => "#eb001b", "paypal" => "#009cde", "visacheckout" => "#191e6d", "visa" => "#f0b100", "googlepay" => "#4086f4", "applepay" => "#2f3a40");
    $hsColorsa = array();
    $mdata = array();
    $declines = array();
    $errors = array();
    $ccounts = array();
    foreach ($pms as $pmethod) {
        $pmethod = strtolower($pmethod);
        $dataItem = array($pmethod, 0);
        $declines[$pmethod] = 0;
        $errors[$pmethod] = 0;
        $ccounts[$pmethod] = 0;
        if (@$stats[$pmethod]) {
            $dataItem = array($pmethod, $stats[$pmethod][$type]);
            if ($type == 'chargeAmount') {
                $declines[$pmethod] = $stats[$pmethod]['declineCount'];
                $errors[$pmethod] = $stats[$pmethod]['errorCount'];
                $ccounts[$pmethod] = $stats[$pmethod]['chargeCount'];
            }
        }
        array_push($mdata, $dataItem);
        $hsColorsa[] = $legendColors[$pmethod];
    }

    $canvLabel = array();
    $canvlabelValues  = array(); 
    foreach($mdata as $mdatav)
    {
    $canvLabel[] = $mdatav[0];
    $canvlabelValues[]=$mdatav[1]==0?0.05:$mdatav[1];
    }
    $label = $type == "chargeAmount" ? __("Earnings per payment method used by customers.") : __("Refunds credited to customer payment methods.");
    $label.= $type == "chargeAmount" ? __(sprintf("Total earnings:%s %s",$batchData["total"],$currency)) : __(sprintf("Total refunds:%s %s",$batchData["refunds"],$currency));


    $script = 'jQuery(()=>{var declines = ' . json_encode((object)$declines) . ';var errors = ' . json_encode((object)$errors) . ';var counts = ' . json_encode((object)$ccounts) . ';var ctype = "' . $type . '";var popCanvas = $('.$canvid.');var barChart = new Chart(popCanvas, {type: "bar",options: {tooltips: {callbacks: {title: function(tooltipItem, data) {str  = tooltipItem[0].label+":"+(tooltipItem[0].value==0.05?0:tooltipItem[0].value)+" '.$currency.'\n";str += "Charge counts:";str += counts[tooltipItem[0].label]+"\n";str += "Declines:";str += declines[tooltipItem[0].label]+"\n";str += "Errors:";str += errors[tooltipItem[0].label]+"\n";return str;},label: function(tooltipItem, data) {return null;},afterLabel: function(tooltipItem, data) {return null;}},backgroundColor: "#F8F8F8",titleFontSize: 12,titleFontColor: "#6C6A62",bodyFontColor: "#6C6A62",bodyFontSize: 14,displayColors: false}},data: {labels: '.json_encode($canvLabel).',datasets: [{label: "'.$label.'",data: '.json_encode($canvlabelValues).',backgroundColor: '.json_encode($hsColorsa).',}]}});})';
    echo $script;
}

/**
 * getPmthumb
 *
 * @param [type] $pm
 * @param [type] $alt
 * @param string $class
 * @return void
 */
function getPmthumb($pm, $alt, $class = 'pm-thumb') {
    $basePathPi = BS_ANET_WC_BASEURL_ASSETS . 'images/paymentmethods/';
    $imgIco = $basePathPi . strtolower($pm) . '.svg';
    return '<img alt="' . $alt . '" class="' . $class . '" width="25%" src="' . $imgIco . '"/>';
}

/**
 * getView
 *
 * @param [type] $vname
 * @return void
 */
function getView($vname) {
    $viewpath = BS_ANET_WC_ADMIN_VIEWS . $vname . '.php';
    require_once $viewpath;
}

/**
 * gettabs
 *
 * @return void
 */
function gettabs() {
    $current = (!empty($_GET['tab'])) ? esc_attr($_GET['tab']) : 'setteledtransactions';
    $tabs = array('setteledtransactions' => array('title' => __('Setteled Transactions', 'woocommerce-authorizenet'), 'icon' => 'icon-money'), 'fraudmanagement' => array('title' => __('Fraud Management', 'woocommerce-authorizenet'), 'icon' => 'icon-exclamation-triangle'), 'unsetteledtransactions' => array('title' => __('Unsetteled Transactions', 'woocommerce-authorizenet'), 'icon' => 'icon-refresh', 'spin'), 'merchantinfo' => array('title' => __('Merchant Info', 'woocommerce-authorizenet'), 'icon' => 'icon-black-tie'), 'advancedsettings' => array('title' => __('Advanced Settings', 'woocommerce-authorizenet'), 'icon' => 'icon-gear'));
    return $tabs;
}

/**
 * is_method_available
 *
 * @param [type] $method
 * @return boolean
 */
function is_method_available($method) {
    $available = false;
    $supported_cards = array('visa', 'mastercard', 'discover', 'dinersclub', 'jcb', 'americanexpress');
    $helper = BS_Anet_WC_helper::getInstance();
    $cards = array();
    $params = $helper->getParams();
    if ($params) {
        $paymenthelper = BS_Anet_WC_payment::getInstance();
        $merchant = $paymenthelper->verifyAnet((array)$params);
        if ($merchant['state']) {
            $paymethods = $merchant['data']['pmethods'];
            switch ($method) {
                case 'C':
                    foreach ($supported_cards as $supported_card) {
                        if (key_exists($supported_card, $paymethods)) {
                            $cards[] = $paymethods[$supported_card];
                        }
                    }
                    $available = array('cards' => $cards);
                break;
                case 'E':
                    if (in_array('echeck', $paymethods)) {
                        $available = $paymethods['echeck'];
                    }
                break;
                case 'P':
                    if (in_array('paypal', $paymethods)) {
                        $available = $paymethods['paypal'];
                    }
                break;
            }
        }
    }
    return $available;
}

/**
 * hasConfiguration
 *
 * @return boolean
 */
function hasConfiguration() {
    $helper = BS_Anet_WC_helper::getInstance();
    $params = $helper->getParams();
    return $params && is_object($params) ? true : false;
}

/**
 * isAcceptSuite
 *
 * @param boolean $post
 * @return boolean
 */
function isAcceptSuite($post = true) {
    $helper = BS_Anet_WC_helper::getInstance();
    $params = $helper->getParams();
    if ($params && wc_checkout_is_https() && is_ssl()  && $params->public_client_key && ($post ? !empty($_POST['dataDescriptor']) && !empty($_POST['dataValue']) : true)) {
        return true;
    } else {
        return false;
    }
}

/**
 * isExistingMethod
 *
 * @param [type] $type
 * @return boolean
 */
function isExistingMethod($type) {
    $return = false;
    $method = 'C' === $type ? BS_ANET_WC_CC_PLUGIN_ID : ('E' === $type ? BS_ANET_WC_ECHECK_PLUGIN_ID : null);
    if ($method) {
        if (isset($_POST['wc-' . $method . '-payment-token']) && !empty($_POST['wc-' . $method . '-payment-token'])) {
            $token = wc_clean($_POST['wc-' . $method . '-payment-token']);
            if ($token != 'new') return $token;
        }
    }
    return $return;
}

/**
 * hasToAddNewMethod
 *
 * @param [type] $type
 * @return boolean
 */
function hasToAddNewMethod($type) {
    $method = 'C' === $type ? BS_ANET_WC_CC_PLUGIN_ID : ('E' === $type ? BS_ANET_WC_ECHECK_PLUGIN_ID : null);
    if ($method) {
        if ((isset($_POST['wc-' . $method . '-new-payment-method']) && !empty($_POST['wc-' . $method . '-new-payment-method']))) {
            $newPaymentMethod = wc_clean($_POST['wc-' . $method . '-new-payment-method']);
            $token = null;
            if ((isset($_POST['wc-' . $method . '-payment-token']) && !empty($_POST['wc-' . $method . '-payment-token']))) $token = wc_clean($_POST['wc-' . $method . '-payment-token']);
            if ('true' === $newPaymentMethod) {
                if (!$token) {
                    return true;
                } else {
                    return 'new' === $token;
                }
            }
        }
    }
    return false;
}

/**
 * cardAnimation
 *
 * @return void
 */
function cardAnimation() {
    $settings = BS_Anet_WC_settings::getInstance();
    if ($settings->get_option('card_animation')==='yes') {
        wp_enqueue_script('anet-cardeffect', BS_ANET_WC_BASEURL_ASSETS . 'card/cardeffect.js', array('jquery'));
        wp_localize_script('anet-cardeffect', 'params', array('containerId' => 'wc-bs_anet_wc_cc-cc-form', 'keyword' => BS_ANET_WC_CC_PLUGIN_ID));
        wp_enqueue_style('anet-cardeffect', BS_ANET_WC_BASEURL_ASSETS . 'card/cardeffect.css');
        $html = '<div style="display:none;" class="anet-creditcard-effect-container"><div style="max-width:'.$settings->get_option('virtual_card_width').'px;" class="anet-creditcard-effect"> <div class="front"> <div id="ccsingle"></div> <svg version="1.1" id="cardfront" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;" xml:space="preserve"> <g id="Front"> <g id="CardBackground"> <g id="Page-1_1_"> <g id="amex_1_"> <path id="Rectangle-1_1_" class="lightcolor grey" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40 C0,17.9,17.9,0,40,0z" /> </g> </g> <path class="darkcolor greydark" d="M750,431V193.2c-217.6-57.5-556.4-13.5-750,24.9V431c0,22.1,17.9,40,40,40h670C732.1,471,750,453.1,750,431z" /> </g> <text transform="matrix(1 0 0 1 60.106 295.0121)" id="svgnumber" class="st2 st3 st4"></text> <text transform="matrix(1 0 0 1 54.1064 428.1723)" id="svgname" class="st2 st5 st6"></text> <text transform="matrix(1 0 0 1 479.7754 388.8793)" class="st7 st5 st8">expiration</text> <text transform="matrix(1 0 0 1 65.1054 241.5)" class="st7 st5 st8">card number</text> <g> <text transform="matrix(1 0 0 1 574.4219 433.8095)" id="svgexpire" class="st2 st5 st9"></text> <text transform="matrix(1 0 0 1 479.3848 417.0097)" class="st2 st10 st11">VALID</text> <text transform="matrix(1 0 0 1 479.3848 435.6762)" class="st2 st10 st11">THRU</text> <polygon class="st2" points="554.5,421 540.4,414.2 540.4,427.9 " /> </g> <g id="cchip"> <g> <path class="st2" d="M168.1,143.6H82.9c-10.2,0-18.5-8.3-18.5-18.5V74.9c0-10.2,8.3-18.5,18.5-18.5h85.3 c10.2,0,18.5,8.3,18.5,18.5v50.2C186.6,135.3,178.3,143.6,168.1,143.6z" /> </g> <g> <g> <rect x="82" y="70" class="st12" width="1.5" height="60" /> </g> <g> <rect x="167.4" y="70" class="st12" width="1.5" height="60" /> </g> <g> <path class="st12" d="M125.5,130.8c-10.2,0-18.5-8.3-18.5-18.5c0-4.6,1.7-8.9,4.7-12.3c-3-3.4-4.7-7.7-4.7-12.3 c0-10.2,8.3-18.5,18.5-18.5s18.5,8.3,18.5,18.5c0,4.6-1.7,8.9-4.7,12.3c3,3.4,4.7,7.7,4.7,12.3 C143.9,122.5,135.7,130.8,125.5,130.8z M125.5,70.8c-9.3,0-16.9,7.6-16.9,16.9c0,4.4,1.7,8.6,4.8,11.8l0.5,0.5l-0.5,0.5 c-3.1,3.2-4.8,7.4-4.8,11.8c0,9.3,7.6,16.9,16.9,16.9s16.9-7.6,16.9-16.9c0-4.4-1.7-8.6-4.8-11.8l-0.5-0.5l0.5-0.5 c3.1-3.2,4.8-7.4,4.8-11.8C142.4,78.4,134.8,70.8,125.5,70.8z" /> </g> <g> <rect x="82.8" y="82.1" class="st12" width="25.8" height="1.5" /> </g> <g> <rect x="82.8" y="117.9" class="st12" width="26.1" height="1.5" /> </g> <g> <rect x="142.4" y="82.1" class="st12" width="25.8" height="1.5" /> </g> <g> <rect x="142" y="117.9" class="st12" width="26.2" height="1.5" /> </g> </g> </g> </g> <g id="Back"> </g> </svg> </div> <div class="back"> <svg version="1.1" id="cardback" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 750 471" style="enable-background:new 0 0 750 471;" xml:space="preserve"> <g id="Front"> <line class="st0" x1="35.3" y1="10.4" x2="36.7" y2="11" /> </g> <g id="Back"> <g id="Page-1_2_"> <g id="amex_2_"> <path id="Rectangle-1_2_" class="darkcolor greydark" d="M40,0h670c22.1,0,40,17.9,40,40v391c0,22.1-17.9,40-40,40H40c-22.1,0-40-17.9-40-40V40 C0,17.9,17.9,0,40,0z" /> </g> </g> <rect y="61.6" class="st2" width="750" height="78" /> <g> <path class="st3" d="M701.1,249.1H48.9c-3.3,0-6-2.7-6-6v-52.5c0-3.3,2.7-6,6-6h652.1c3.3,0,6,2.7,6,6v52.5 C707.1,246.4,704.4,249.1,701.1,249.1z" /> <rect x="42.9" y="198.6" class="st4" width="664.1" height="10.5" /> <rect x="42.9" y="224.5" class="st4" width="664.1" height="10.5" /> <path class="st5" d="M701.1,184.6H618h-8h-10v64.5h10h8h83.1c3.3,0,6-2.7,6-6v-52.5C707.1,187.3,704.4,184.6,701.1,184.6z" /> </g> <text transform="matrix(1 0 0 1 621.999 227.2734)" id="svgsecurity" class="st6 st7"></text> <g class="st8"> <text transform="matrix(1 0 0 1 518.083 280.0879)" class="st9 st6 st10">security code</text> </g> <rect x="58.1" y="378.6" class="st11" width="375.5" height="13.5" /> <rect x="58.1" y="405.6" class="st11" width="421.7" height="13.5" /> <text transform="matrix(1 0 0 1 59.5073 228.6099)" id="svgnameback" class="st12 st13"></text> </g> </svg> </div> </div></div>';
        return $html;
    }
}

/**
 * add_init_hooks
 *
 * @return void
 */
function add_init_hooks() {
  BS_Anet_WC_settings::getInstance();
    if (is_admin()) {
        //init backend reports and ajax callbacks
        require_once ('class-bs-anet-wc-reports.php');
        $reports = BS_Anet_WC_Reports::getInstance();
        $reports->initReports();
        add_action('wp_ajax_anet_setteled_transactions', array($reports, 'anet_setteled_transactions'));
        add_action('wp_ajax_anet_transaction_details', array($reports, 'anet_transaction_details'));
        add_action('wp_ajax_anet_transaction_void', array($reports, 'anet_transaction_void'));
        add_action('wp_ajax_anet_transaction_refund', array($reports, 'anet_transaction_refund'));
        add_action('wp_ajax_anet_transaction_fdsaction', array($reports, 'anet_transaction_fdsaction'));
        add_action('woocommerce_admin_order_data_after_order_details', array($reports, 'view_anet_transaction'));
    }
    add_filter('woocommerce_payment_methods_list_item', array(WC_Gateway_anet_Token_Helper::getInstance(), 'wc_get_account_saved_payment_methods_list_item_anet'), 10, 2);
    add_filter('woocommerce_get_customer_payment_tokens', array(WC_Gateway_anet_Token_Helper::getInstance(), 'woocommerce_get_customer_payment_anet_tokens'), 10, 3);
    add_action('woocommerce_payment_token_set_default', array(WC_Gateway_anet_Token_Helper::getInstance(), 'payment_token_set_default_anet'));
    add_action('woocommerce_payment_token_deleted', array(WC_Gateway_anet_Token_Helper::getInstance(), 'payment_token_delete_pm_anet'), 10, 2);
    add_action( 'delete_user', array(BS_Anet_WC_customer::getInstance(),'delete_customer') );
    add_action('wp_ajax_validatecard', array('BS_Anet_WC_formvalidator', 'validateCard'));
    add_action('wp_ajax_validateecheck', array('BS_Anet_WC_formvalidator', 'validateEcheck'));
}
