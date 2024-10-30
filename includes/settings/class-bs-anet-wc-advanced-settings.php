<?php

/**
 * WordPress settings API demo class
 *
 * @author Tareq Hasan
 */
if ( !class_exists('BS_Anet_WC_settings' ) ):
class BS_Anet_WC_settings {

    private $settings_api;
    private static $instance;
    private $defaults = array('duplicate_window'=>0,'save_paymethod'=>'off','card_animation'=>'yes','default_ostatus'=>'wc-on-hold','virtual_card_width'=>300);


    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BS_Anet_WC_settings();
        }
        return self::$instance;
    }

    function __construct() {
        $this->settings_api = new WCANET_Settings_API;

        add_action( 'admin_init', array($this, 'admin_init') );
       // add_action( 'admin_menu', array($this, 'admin_menu') );
    }

    function admin_init() {

        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    function admin_menu() {
        add_options_page( 'Settings API', 'Settings API', 'delete_posts', 'settings_api_test', array($this, 'plugin_page') );
    }

    function get_settings_sections() {
        $sections = array(
            array(
                'id'    => 'advanced_settings',
                'title' => __( 'Advanced Settings', 'bs_anet_wc' )
            )
        );
        return $sections;
    }

   function get_option($option)
    {
          $default = $this->getDefault($option);
          return  $this->settings_api->get_option( $option, 'advanced_settings', $this->getDefault($option));
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    function get_settings_fields() {
        $settings_fields = array(
            'advanced_settings' => array(
                
                array(
                    'name'  => 'save_paymethod',
                    'label' => __( 'Save payment method', 'bs_anet_wc' ),
                    'desc'  => __( 'Allow customers to save payment method.', 'bs_anet_wc' ),
                    'default'=> $this->defaults['save_paymethod'],
                    'type'  => 'checkbox'
                ),
                array(
                    'name'    => 'default_ostatus',
                    'label'   => __( 'Default order status', 'bs_anet_wc' ),
                    'desc'    => __( 'Default order status after successful checkout', 'bs_anet_wc' ),
                    'type'    => 'select',
                    'default'=> $this->defaults['default_ostatus'],
                    'options' => wc_get_order_statuses()
                ),
                array(
                    'name'              => 'duplicate_window',
                    'label'             => __( 'Duplicate transaction window', 'bs_anet_wc' ),
                    'desc'              => __( 'Set duplicate transaction window time in minutes.Transactions with the same data info submitted within the time set here will be marked duplicate.Authorize.Net looks for transactions which are likely to be duplicates by matching the data(Amount,Firstname,Last name,Zip code, Address and many more fields) provided with the transaction.Set 0 for no duplicate transactions checking.Positive non zero number upto 480 for duplicate transaction window time.', 'bs_anet_wc' ),
                    'min'               => 0,
                    'max'               => 480,
                    'step'              => '1',
                    'type'              => 'number',
                    'default'           => $this->defaults['duplicate_window'],
                    'sanitize_callback' => 'intval'
                ),
                array(
                    'name'    => 'card_animation',
                    'label'   => __( 'Enable virtual card', 'bs_anet_wc' ),
                    'desc'    => __( 'Customers see animated virtual card while entering their card information.Click no to disable virtual card.', 'bs_anet_wc' ),
                    'type'    => 'radio',
                    'default'=> $this->defaults['card_animation'],
                    'options' => array(
                        'yes' => 'Yes',
                        'no'  => 'No'
                    )
                ),
                array(
                    'name'              => 'virtual_card_width',
                    'label'             => __( 'Virtual card width', 'bs_anet_wc' ),
                    'desc'              => __( 'Applicable only if allowed to display.', 'bs_anet_wc' ),
                    'min'               => 0,
                    'max'               => 500,
                    'step'              => '1',
                    'type'              => 'number',
                    'default'           => $this->defaults['virtual_card_width'],
                    'sanitize_callback' => 'intval'
                )
            )
        );
        return $settings_fields;
    }

    function plugin_page() {
       
        echo  settings_errors();
        $this->settings_api->show_navigation();
        $this->settings_api->show_forms();

        
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    function get_pages() {
        $pages = get_pages();
        $pages_options = array();
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
    }

    function getDefault($option)
    {
         return $this->defaults[$option];
    }

}
endif;
