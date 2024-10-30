<?php 
$helper = BS_Anet_WC_helper::getInstance();

?>


    <div id="icon-options-general" class="icon32"></div>
    <h1>
        <?php esc_attr_e( 'Advanced settings', 'WpAdminStyle' ); ?>
    </h1>

    <div id="poststuff">

        <div id="post-body" class="metabox-holder columns-2">

            <!-- main content -->
            <div id="post-body-content">

               <?php 
               
               $settings =  BS_Anet_WC_settings::getInstance();
               $settings->plugin_page();
               ?>


            </div>
            <!-- post-body-content -->

           

        </div>
        <!-- #post-body .metabox-holder .columns-2 -->

        <br class="clear">
    </div>
    <!-- #poststuff -->

