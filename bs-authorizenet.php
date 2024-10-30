<?php
/**
 * Plugin Name: BS Authorize.net
 * Plugin URI: https://www.thebipin.com/
 * Description: Most advanced Authorize.net payment gateway for woocommerce.Live merchant dashboard to access live transaction reports directly from authorize.net.Manage transactions. View unsettled transactions .Void and refund transactions.Just activate the plugin and You will find your merchant dashboard link under woocommerce menu.
 * Author: Bipin Singh
 * Author URI: https://www.thebipin.com
 * Copyright: © 2019 Bipin Singh.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain: bs_anet_wc
 * Domain Path: /languages
 * WC tested up to: 5.2
 * WC requires at least: 2.6
 * Version: 1.0
 */
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('BS_ANET_WC_ABSPATH',plugin_dir_path(__FILE__));
define('BS_ANET_WC_BASEURL',plugin_dir_url(__FILE__));


add_action( 'plugins_loaded', 'bs_anet_wc_init', 0 );

function bs_anet_wc_init() {
  

 
  if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) )
  {

  
  if ( ! class_exists( 'BS_Anet_WC_bootsrap' ) ) :
    class BS_Anet_WC_bootsrap 
    {
        private static $instance;
    
        public static function getInstance()
        {
            if (!isset(self::$instance)) {
                self::$instance = new BS_Anet_WC_bootsrap();
            }
            return self::$instance;
        }
    
        private function __construct()
        {

          $this->bootstrap();
            
        }
    
        public function bootstrap()
        {
          //load includes
          require_once('includes/includes.php');
          add_filter( 'woocommerce_payment_gateways', array( $this, 'loadGateways' ) );
          add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'actionLinks' ) );
        }
    
        public function loadGateways($methods)
        {
          
          $methods[] = 'BS_Anet_WC_Cc';
          $methods[] = 'BS_Anet_WC_Echeck';
          $methods[] = 'BS_Anet_WC_PayPal';

          return $methods;  
        }
    
        public function actionLinks($links)
        {
          $plugin_links = array(
            '<a href="admin.php?page=wc-settings&tab=checkout&section=bs_anet_wc_cc">' . esc_html__( 'Settings', 'bs_anet_wc' ) . '</a>',
            '<a href="https://www.thebipin.com/bs-authorize-net/">' . esc_html__( 'Docs', 'bs_anet_wc' ) . '</a>',
            '<a href="https://wordpress.org/support/plugin/bs-authorize-net/">' . esc_html__( 'Support', 'bs_anet_wc' ) . '</a>',
            '<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=VFF7RD98FVJLL&source=url">' . esc_html__( 'Donate', 'bs_anet_wc' ) . '</a>'
          );
          return array_merge( $plugin_links, $links );
        }
    
        
    
    }
    BS_Anet_WC_bootsrap::getInstance();
    endif;
  }
  else
  {

    add_action( 'admin_notices',function(){
      $class = 'notice notice-error';
        $message = __( ' BS Authorize.net woocomerce payment gateway requires WooCommerce to be installed and active. You can download WooCommerce <a href="https://wordpress.org/plugins/woocommerce/">here</a>.', 'bs-qrcode' );
          printf( '<div class="%1$s"><p><strong>%2$s</strong></p></div>', esc_attr( $class ), $message); 
    } );
  }
   
}








