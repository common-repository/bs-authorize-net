<?php 
   $helper = BS_Anet_WC_helper::getInstance();

?>
<div class="wrap wrap-anet-transaction">
   <?php if($return['state']!=0 && $return['data']) {
      $trans = (object) $return['data'];
    prd($trans);
      ?>
   <h3 class="wp-heading-ref">Transaction <?php echo $trans->getTransId; ?></h3>
   <div class="anet-transactions-details">
   <table class="anet-trans-table">
      <tr>
      </tr>
      

   </table>
</div>
   <?php } elseif($return['state']==0 && $return['data']) { ?>
   <div class="anet_gateway_message">
      <p><?php echo $return['data']; ?></p>
   </div>
   <?php } ?>
</div>
<script>jQuery('.has_bs_anet_tip').tooltipster({functionPosition: function(instance, helper, position){
          position.coord.top += -20;
          position.coord.left += 100;
          return position;
      }});</script>