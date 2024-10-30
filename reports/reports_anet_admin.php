<?php
$tabs = gettabs();
$tab = isset($_GET['tab']) && !empty($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'dashboard';
$helper= BS_Anet_WC_helper::getInstance();
$params = $helper->getParams();

?>

<?php if(!$params){ ?>
        <div class="notice notice-error"><p><?php echo __(sprintf('Merchant Dashboard requires Authorize.net Merchant Api login and transaction key.Please configure them <a href="%s">here</a>','admin.php?page=wc-settings&tab=checkout&section=bs_anet_wc_cc')); ?></p></div>
      <?php } ?>


<div class="wrap anet-wrap">
   <h1 class="wp-heading-inline">
  
   </h1><?php echo $tab !== 'dashboard' ? '<a class="button-primary right" href="admin.php?page=merchantdashboard">Go back to dashboard</a>' : ''; ?>
   <?php
if ($tab !== 'dashboard') {
    switch ($tab) {
        case 'setteledtransactions':
            getView('settled-transactions');
        break;
        case 'fraudmanagement':
            getView('fraud-management');
        break;
        case 'unsetteledtransactions':
            getView('unsettled-transactions');
        break;
        case 'advancedsettings':
            getView('advanced-settings');
        break;
        case 'merchantinfo':
            getView('merchantinfo');
        break;
    }
} else {
?>



<div id="poststuff">

    <div id="post-body" class="metabox-holder columns-2">

        <!-- main content -->
        <div id="post-body-content">
<table>
   <tbody>
    <tr><td class="anet_merch_db_left" valign="top"></td>
        <td class="anet_merch_db_center">
        <div class="merch_heading"><h3>Merchant Dashboard</h3></div>    
        <div class="anet_merch_db_center_main">
        <ul class="tab-items">

<?php foreach ($tabs as $tabk => $tabv) { 
$link = $params?"admin.php?page=merchantdashboard&tab=".$tabk:'#';
?>
<li class=""><a  class="<?php echo !$params?"no_drop_cursor":""; ?>" href="<?php echo $link; ?>"><p><i class="fa <?php echo $tabv['icon']; ?> fa-5x"></i></p><p><?php echo $tabv['title']; ?></p></a></li>

<?php
    } ?>

   </ul>
        </div>
        </td>
        <td class="anet_merch_db_right" valign="top">
        
       
        
        </td>
    </tr>
   </tbody>
</table>
        
</div>
        <!-- post-body-content -->

        <!-- sidebar -->
        <div id="postbox-container-1" class="postbox-container">

            <div class="meta-box-sortables">
            <div class="postbox anet_accep_status">

<h2><span><?php esc_attr_e(
            'Authorize.net accept suite status', 'WpAdminStyle'
        ); ?></span></h2>

<div class="inside">
    <p class="accept_suit <?php echo isAcceptSuite(false)?'green':'red'; ?>"><b>Accept suite is <?php echo isAcceptSuite(false)?'enabled':'disabled'; ?></b></p>
    <?php if(isAcceptSuite(false)){ ?>
    <p>
        <?php echo __('Your payment gateway is PCI Compliant.','bs_anet_wc');?>
    </p>
    <?php } else { ?>
    <p>
        <?php echo __('Authorize.Net Accept is a suite of developer tools for building websites without increasing PCI burden for merchants.BS Authorize.net WC plugin implements these tools and is PCI-DSS SAQ A-EP compliant solution.Payment data is transmitted to Authorize.net servers in a completely encrypted form.','bs_anet_wc');?>
    </p>
    <p>
    <?php echo __('Accept suite has following requirements.','bs_anet_wc');?>
    </p>    
      <p>
      <ul>
        <li><?php echo __(sprintf('1.You must enable <a target="_blank" href="%s">force SSl</a> in woocomerce.','admin.php?page=wc-settings&tab=advanced'),'bs_anet_wc');?></li>
        <li><?php echo __(sprintf('2.Your site must be served on <a target="_blank" href="%s">https</a>.','http://www.howto-expert.com/how-to-get-https-setting-up-ssl-on-your-website/'),'bs_anet_wc');?></li>
      </ul>
      </p>
    <p>
        <?php echo __('Whenever these two requirements are met. Plugin automatically activates the accept suit tools.','bs_anet_wc');?>
    </p>
    <p>
        <b><?php echo __('However if you\'r website doesn\'t meet the above requirements.BS Authorize.net still works without Accept suite.But that is not the recommended approach','bs_anet_wc');?></b>
    </p>
    <?php } ?>
</div>
<!-- .inside -->

</div>
                
                <!-- .postbox -->

            </div>
            <!-- .meta-box-sortables -->

        </div>
        <!-- #postbox-container-1 .postbox-container -->

    </div>
    <!-- #post-body .metabox-holder .columns-2 -->

    <br class="clear">
</div>
<!-- #poststuff -->




   
   
</div>

<?php
} ?>