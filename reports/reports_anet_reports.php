<?php 


$b_id = (int)sanitize_text_field($_GET['batch_id']);
?>




       
<div class="wrap wrap-anet-reports">
<?php if($return['state']!=0 && $return['data']) { 
     $numCount = $return['total'];
     $limit = $return['limit'];
     $offset = $return['offset'];
    ?>
   <h1 class="wp-heading-inline">
      <?php echo __('Transactions list batch id','bs_anet_wc'); ?>: <?php echo $b_id; ?>
   </h1>
   
<input type="hidden" id="batch_id" value="<?php echo  $b_id;  ?>"/>
  
         <form method="post" action="?page=merchantdashboard">
      <div class="tablenav top">
         <div class="alignleft actions bulkactions">
            
            
         </div>

         <div class="alignright actions bulkactions">
         <div id="pagination"></div>
         <input type="hidden" id="offset" value="<?php echo $offset ?>">
         <input type="hidden" id="limit" value="<?php echo $limit ?>">
         <?php if($numCount>=$limit){ ?>
         <a style="display:none;" id="prevbtn" onclick="loadnext(event,this,'p');" class="view-transactions transbtn-md nprev"><i class="icon-arrow-circle-o-left"></i>Load prev <?php echo $limit; ?></i></a>  
         <a  onclick="loadnext(event,this,'n');" id="nextbtn" class="view-transactions transbtn-md nprev">Load next <?php echo $limit; ?><i class="icon-arrow-circle-o-right"></i></a>  
         <?php } ?>
         </div>
      </div>
      
     
         <h2><?php echo __('Transactions list','bs_anet_wc'); ?></h2>
         <div class="anet-transactions-list anet-transactions-list-setteled">
      <table class="wp-list-table widefat fixed batch-list-table" border="0">
	<thead>
		<tr>
                
                <th scope="col" class="column-primary batch-col-md"><?php echo __('Transaction ID','bs_anet_wc'); ?></th>
                <th scope="col"  class="column-primary "><?php echo __('Transaction Date(UTC)','bs_anet_wc'); ?></th>
				<th scope="col"  class="column-primary "><?php echo __('Transaction Date','bs_anet_wc'); ?></th>
				<th scope="col"  class="column-primary"><?php echo __('Transaction status','bs_anet_wc'); ?></th>
				<th scope="col"  class="column-primary"><?php echo __('Customer','bs_anet_wc'); ?></th>
				<th scope="col"  class="column-primary"><?php echo __('Account type','bs_anet_wc'); ?></th>
                <th scope="col"  class="column-primary"><?php echo __('Amount','bs_anet_wc'); ?></th>
				<th scope="col"  class="column-primary"><?php echo __('Actions','bs_anet_wc'); ?></th>
			</tr>
        </thead>
        <script>dispTrans('<?php echo json_encode($return['data']); ?>');</script>
		<tbody id="transaction-rows"></tbody>
						<tfoot>
							
							</tfoot>
                        </table>
      </div>

                  <div class="tablenav bottom">


<div class="tablenav-pages one-page">Displaying &nbsp;<span id="transact-disp-num" class="displaying-num"></span>&nbsp; items</div>
<br class="clear">
</div>

      

                  

</form>
<?php } elseif($return['state']==0 && $return['data']) { ?>
       <div class="anet_gateway_message"><p><?php echo $return['data']; ?></p></div>
    
      <?php } ?>   
   
</div>