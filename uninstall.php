<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 
$advanced_settings = 'wc-anet-advanced-settings-group';
$cc_options        = 'woocommerce_bs_anet_wc_cc_settings';
$echeck_options    = 'woocommerce_bs_anet_wc_echeck_settings';
$paypal_options    = 'woocommerce_bs_anet_wc_paypal_settings';
 
delete_option($advanced_settings);
delete_option($cc_options);
delete_option($echeck_options);
delete_option($paypal_options);

delete_site_option($advanced_settings);
delete_site_option($cc_options);
delete_site_option($echeck_options);
delete_site_option($paypal_options);

?>