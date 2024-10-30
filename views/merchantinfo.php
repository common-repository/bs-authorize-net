<?php 
$helper = BS_Anet_WC_helper::getInstance();

$params = $helper->getParams();

$settings =   BS_Anet_WC_settings::getInstance();

$data = null;

if($params)
{
   $payment = BS_Anet_WC_payment::getInstance();
        

   $data =  $payment->verifyAnet((array)$params);

}

?>

<?php if($data && $data['state']==1){ 
   $merchant = $data['data'];
   ?>
<div class="tablenav top">
    <div class="alignleft actions bulkactions">

        <h2>Authorize.net merchant info</h2>
    </div>
</div>
<table class="widefat">

    <tbody>

        <tr>
            <td class="row-title"><label for="tablecell"><?php esc_attr_e(
					'Merchant name', 'WpAdminStyle'
				); ?></label></td>
            <td>
                <?php esc_attr_e( $merchant['mname'], 'WpAdminStyle' ); ?>
            </td>
        </tr>
        <tr>
            <td class="row-title"><label for="tablecell"><?php esc_attr_e(
					'Gateway ID', 'WpAdminStyle'
				); ?></label></td>
            <td>
                <?php esc_attr_e( $merchant['gid'], 'WpAdminStyle' ); ?>
            </td>
        </tr>
        <tr>
            <td class="row-title">
                <?php esc_attr_e(
				'Other Fields', 'WpAdminStyle'
			); ?>
            </td>
            <td>
                <?php esc_attr_e( implode("&nbsp;",$merchant['fields']), 'WpAdminStyle' ); ?>
            </td>
        </tr>
        <tr>
            <td class="row-title">
                <?php esc_attr_e(
				'Available Payment methods', 'WpAdminStyle'
			); ?>
            </td>
            <td>
                <?php foreach($merchant['pmethods'] as $paymentmethod){
       echo  $paymentmethod."&nbsp;";
      } ?>
            </td>
        </tr>

        <tr>
            <td class="row-title">
                <?php esc_attr_e(
				'Supported Currencies', 'WpAdminStyle'
			); ?>
            </td>
            <td>
                <?php esc_attr_e( implode("&nbsp;",$merchant['currencies']), 'WpAdminStyle' ); ?>
            </td>
        </tr>
        <tr>
            <td class="row-title">
                <?php esc_attr_e( 'Public client key', 'WpAdminStyle' ); ?>
            </td>
            <td>
                <?php esc_attr_e( $merchant['public_client_key'], 'WpAdminStyle' ); ?>
            </td>
        </tr>

    </tbody>

</table>

<?php } else { ?>View Transactions

<div class="anet_gateway_message">
    <p>
        <?php echo $data['data']?$data['data']:__('Please configure your gateway first','bs_anet_wc'); ?>
    </p>
</div>
<?php } ?>