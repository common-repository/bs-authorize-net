<?php 
   $helper = BS_Anet_WC_helper::getInstance();
   
?>
<div class="wrap wrap-anet-transaction">
   <?php if($return['state']!=0 && $return['data']) {
      $trans = (object) $return['data'];

     

      $wcorder = $trans->wc_order;
      if($wcorder):
      $payMethod = $wcorder->get_meta('_wc_anet_pm');
      else:
      $payMethod = $trans->pay_type;
      endif;   
      $payMethodDetails = $trans->paymethod;

      //Address verification service
      $AVSResponse         = $trans->getAVSResponse;
      $getCardCodeResponse = $trans->getCardCodeResponse;
      //cardholder authentication verification value
      $CAVVResponse        = $trans->getCAVVResponse;
      //Fraud detection service filter action
      $FDSFilterAction     = $trans->getFDSFilterAction;
      // fraud detection service filters
      $FDSFilters          = $trans->getFDSFilters;
      ?>
   <h3 class="wp-heading-ref">Transaction <?php echo $trans->getTransId; ?></h3>
   <div class="anet-transactions-details">
   <table class="anet-trans-table">
      <tr>
     
      <td valign="top">
      <table id="trans_desc_<?php echo $trans->getTransId; ?>" class="trans_desc">
      <tr>
            <td><h3><?php echo __('Transaction details','bs_anet_wc'); ?>:</h3></td>
            <td></td>
         </tr>
         <tr>
            <td><?php echo __('Transaction id','bs_anet_wc'); ?>:</td>
            <td><b><?php echo $trans->getTransId; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('Auth amount','bs_anet_wc'); ?>:</td>
            <td><b><?php echo $trans->authAmount.' '.$trans->currency; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('Settle amount','bs_anet_wc'); ?>:</td>
            <td><b><?php echo $trans->settleAmount.' '.$trans->currency; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('Submitted date(UTC)','bs_anet_wc'); ?>:</td>
            <td><b><?php echo $trans->getSubmitTimeUTC; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('Submitted date','bs_anet_wc'); ?>:</td>
            <td><b><?php echo $trans->getSubmitTimeLocal; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('Transaction Type','bs_anet_wc'); ?>:</td>
            <td><b class="highlight-sky highlight-trs"><?php echo $trans->getTransactionType; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('Transaction Status','bs_anet_wc'); ?>:</td>
            <td><b class="tstate <?php echo $trans->getTransactionStatus; ?>"><?php echo $trans->getTransactionStatus; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('Transaction response code','bs_anet_wc'); ?>:</td>
            <td><b><?php echo $trans->getResponseCode; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('transaction response reason code','bs_anet_wc'); ?>:</td>
            <td><b><?php echo $trans->getResponseReasonCode; ?></b></td>
         </tr>
         <tr>
            <td><?php echo __('Transaction response description','bs_anet_wc'); ?>:</td>
            <td><b title="<?php echo $trans->getResponseReasonDescription; ?>" class="highlight-sky highlight-trs has_bs_anet_tip"><?php echo $trans->getResponseReasonDescription; ?></b></td>
         </tr>
         <?php if($AVSResponse){ ?>
         <tr>
            <td><?php echo __('AVS(Address verification suit) Response','bs_anet_wc'); ?>:</td>
            <td><a class="has_bs_anet_tip" title="Click to know more." href="https://account.authorize.net/help/Account/Settings/Security_Settings/Fraud_Settings/Address_Verification_System_(AVS).htm" target="_blank"><?php echo $AVSResponse; ?></a></td>
         </tr>
         <?php } ?>
         <?php if($getCardCodeResponse){ ?>
         <tr >
            <td><?php echo __('Card code response','bs_anet_wc'); ?>:</td>
            <td> <a class="has_bs_anet_tip" title="Click to know more" href="https://support.authorize.net/s/article/What-Is-Card-Code-Verification" target="_blank"><?php echo $getCardCodeResponse; ?></a></td>
         </tr>
         <?php } ?>
         <?php if($CAVVResponse){ ?>
         <tr >
            <td><?php echo __('CAVV(Card holder Authentication Verification Value) Response','bs_anet_wc'); ?>:</td>
            <td><?php echo $CAVVResponse; ?></td>
         </tr>
         <?php } ?>
         <?php if($FDSFilterAction){ ?>
         <tr class="red">
            <td><?php echo __('FDS(Fraud Detection Service) filter action','bs_anet_wc'); ?>:</td>
            <td><?php echo $FDSFilterAction; ?></td>
         </tr>
         <?php } ?>
         <?php if($FDSFilters){ ?>
         <tr class="red">
            <td valign="top"><?php echo __('FDS filters triggered','bs_anet_wc'); ?>:</td>
            <td valign="top">
               <?php foreach($FDSFilters as $FDSFilter) {?>
                  <p class="nomg"><?php echo __('Name','bs_anet_wc'); ?>: <?php echo $FDSFilter->getName(); ?> | Action: <?php  echo $FDSFilter->getAction(); ?> <p>
                 

               <?php } ?>
               
               </td>
         </tr>
         <?php } ?>
        

         <?php if('capturedPendingSettlement'===$trans->getTransactionStatus || 'underReview'===$trans->getTransactionStatus || 'refundPendingSettlement'===$trans->getTransactionStatus){ ?>
            <tr>
            <td></td>
           
            
            
           
            <td>
            <div style="display:none;"  id="void_msg_<?php echo $trans->getTransId; ?>">
               <p><b>What is void transaction?</b></p>
               <p>A void transaction is a transaction that is canceled by a merchant or vendor before it settles through a consumer's payment method(Debit card,Credit card etc). Transaction id <b><?php echo $trans->getTransId; ?>"</b> is still in <b><?php echo $trans->getTransactionStatus;?></b> state.</p>
               <p>When a transaction takes place on Authorize.net. If there are enough funds in the customer's account, authorize.net authorizes the transaction. But the transaction is not fully settled, as payment has to be released from the customer's account to the merchant.Voiding a transaction cancels the authorization process and prevents the transaction from being submitted to the processor for settlement.</p>
               <p><b>How void transactions are different from refunds?</b></p>
               <p>Void transactions are different from refunds. With void transactions, no money is ever actually transferred from the customer&rsquo;s payment method to the merchant. But refunds are issued after a transaction has settled and the customer has paid for the product.</p>
               <p>You can read more about void transactions <a target='_blank' href='https://www.investopedia.com/terms/v/void-transaction.asp'>here</a> and how they are processed on Authorize.net <a target='_blank' href='https://account.authorize.net/help/Search/Transaction_Detail/Void_a_Transaction.htm'>here</a></p>
               <p><b>Click void to proceed with void process. Cancel to cancel.</b></p>
            </div>
            <a title="Click to know more." onclick= "voidtransact(event,this,'<?php echo $trans->getTransId; ?>','<?php echo $wcorder?$wcorder->get_id():0 ; ?>');" class="transact-dngr has_bs_anet_tip"><?php echo __('Void this transaction','bs_anet_wc'); ?></a></td>
         </tr>
         <?php } if('FDSPendingReview'===$trans->getTransactionStatus || 'FDSAuthorizedPendingReview'=== $trans->getTransactionStatus){ ?>
            <tr>
            <td></td>
            <td><a title="<?php echo __('Click to approve this transaction.','bs_anet_wc'); ?>" onclick= "fdstransactAction(event,this,'<?php echo $trans->getTransId; ?>','A');" class="transact-warn has_bs_anet_tip"><?php echo __('Approve this transaction','bs_anet_wc'); ?></a>   <a title="<?php echo __('Click to decline this transaction.','bs_anet_wc'); ?>" onclick= "fdstransactAction(event,this,'<?php echo $trans->getTransId; ?>','D');" class="transact-warn has_bs_anet_tip"><?php echo __('Decline this transaction','bs_anet_wc'); ?></a></td></td>
         </tr>

         <?php } ?>   
      </table>
      </td>
      <td  valign="top">
     <?php if($type!='refund'){ ?>
      <table class="pm_desc">
      <tr>
      
            <td><h3><?php echo __('Payment method','bs_anet_wc'); ?>:</h3></td>
            <td></td>
         </tr>

         <?php if('card'===$payMethod || 'saved_token_C'===$payMethod){ 
          
            ?>
         
         <tr>
            <td><?php echo __('Card type','bs_anet_wc'); ?>:</td>
            <td><span><?php echo getPmthumb($payMethodDetails->getCardType(),$payMethodDetails->getCardType()); ?></span></td>
         </tr>
         <tr>
            <td><?php echo __('Card number','bs_anet_wc'); ?>:</td>
            <td><?php echo $payMethodDetails->getCardNumber(); ?></td>
         </tr>

         <tr>
            <td><?php echo __('Expiration date','bs_anet_wc'); ?>:</td>
            <td><?php echo $payMethodDetails->getExpirationDate(); ?></td>
         </tr>

         <?php } elseif('echeck'===$payMethod || 'saved_token_E'===$payMethod){
        
            ?> 
               <tr>
            <td><?php echo __('Type','bs_anet_wc'); ?>:</td>
            <td><span><?php echo getPmthumb('eCheck','Echeck'); ?></span></td>
         </tr>
         <tr>
            <td><?php echo __('Account holder','bs_anet_wc'); ?>:</td>
            <td><?php echo  $payMethodDetails->getNameOnAccount(); ?></td>
         </tr>
         <tr>
            <td><?php echo __('Routing Number','bs_anet_wc'); ?>:</td>
            <td><?php echo  $payMethodDetails->getRoutingNumber(); ?></td>
         </tr>

         <tr>
            <td><?php echo __('Account number','bs_anet_wc'); ?>:</td>
            <td><?php echo  $payMethodDetails->getAccountNumber(); ?></td>
         </tr> 


            <?php } elseif('paypal'===$payMethod){ ?>
               <tr>
               <td><?php echo __('Type','bs_anet_wc'); ?>:</td>
               <td><span><?php echo getPmthumb('paypal','Paypal'); ?></span></td>
               </tr>
               
              <?php  if($payMethodDetails){ ?> 
             

            <tr>
            <td><?php echo __('Payer\'s paypal email','bs_anet_wc'); ?>:</td>
            <td><?php echo $payMethodDetails['payer_email'];?></td>
            </tr>

            <tr>
            <td><?php echo __('Payer Id','bs_anet_wc'); ?>:</td>
            <td><?php echo $payMethodDetails['payer_id'];?></td>
            </tr>
             
               <?php }}  ?>

               <?php if('saved_token_E'===$payMethod || 'saved_token_C'===$payMethod){ ?>
                  <tr>
                 <td></td>
                 <td><i><?php echo __('Customer has saved this payment method with your merchant account.'); ?></i></td>
            </tr>
               <?php } ?>
      </table>

               <?php } ?>
    
      </td>
      </tr>

      


      <tr>
      <td valign="top">

      <?php 
      $customer = $trans->getCustomer;
      $billTo = null;
      $shipTo = null;
      if($customer){
            $billTo   = $trans->getBillTo;
            $shipTo   = $trans->getShipTo;
                 }
                 ?>
      <table class="cust_desc">
      
      <tr>
         <td><h3><?php echo __('Customer','bs_anet_wc'); ?>:</h3></td>
            <td></td>
         </tr>
         <?php
      $profile = $trans->getProfile;

     
      
      if($shipTo && $customer){
      ?>

         <tr>
            <td><?php echo __('Name','bs_anet_wc'); ?>:</td>
            <td><?php echo $shipTo->getFirstName().' '.$shipTo->getLastName();?></td>
         </tr>
         <tr>
            <td><?php echo __('Email','bs_anet_wc'); ?>:</td>
            <td><?php echo  $customer->getEmail(); ?></td>
         </tr>

         <tr>
            <td><?php echo __('Ip Address','bs_anet_wc'); ?>:</td>
            <td><?php echo  $trans->getCustomerIP; ?></td>
         </tr>

         <?php if($profile){ ?>

            <tr>
            <td><?php echo __('Profile id','bs_anet_wc'); ?>:</td>
            <td><?php echo  $profile->getCustomerProfileId(); ?></td>
         </tr>

         <tr>
            <td><?php echo __('Payment profile id','bs_anet_wc'); ?>:</td>
            <td><?php echo  $profile->getCustomerPaymentProfileId(); ?></td>
         </tr>

         <?php } ?>
         
      <?php } else { ?>  
         <tr>
            <td><b class="red"><?php echo __('Not found','bs_anet_wc'); ?></b></td>
            <td></td>
         </tr> 
      <?php } ?>
      </table>
      
      </td>
      
      <td valign="top">
      

<table class="order_desc">
      <tr>
      
            <td><h3><?php echo __('Order','bs_anet_wc'); ?>:</h3></td>
            <td></td>
         </tr>
         <?php if(!empty($wcorder) && $wcorder->get_order_number()){ ?>
         <tr>
            <td><?php echo __('Order number','bs_anet_wc'); ?>:</td>
            <td><?php echo '#'.$wcorder->get_order_number();?></td>
         </tr>

         <tr>
            <td><?php echo __('Order key','bs_anet_wc'); ?>:</td>
            <td><?php echo $wcorder->get_order_key();?></td>
         </tr>

         <tr>
            <td><?php echo __('order status','bs_anet_wc'); ?></td>
            <td><b class="highlight-sky highlight-trs"><?php echo $wcorder->get_status();?></b></td>
         </tr>

         <tr>
            <td><?php echo __('Order Total','bs_anet_wc'); ?>:</td>
            <td><?php echo $wcorder->order_total.' '.$wcorder->get_order_currency();?></td>
         </tr>

         <tr>
            <td valign="top"><?php echo __('Order items','bs_anet_wc'); ?>:</td>
            <td><div class="item-meta nice-scroll"><?php if(count($trans->wc_order_items)){  ?>
               <table class="oitem"><tbody>
               <?php foreach($trans->wc_order_items as $item){ 
                  $image =  wp_get_attachment_image_src( get_post_thumbnail_id( $item->get_product_id() ), 'single-post-thumbnail' );
                  $image=$image?$image[0]:wp_upload_dir()['baseurl']."/woocommerce-placeholder-324x324.png";
                  ?>
               <tr><td><?php echo $item->get_name(); ?>(<?php echo $item->get_total().' '.$wcorder->get_order_currency(); ?>)</td><td><img src="<?php echo $image; ?>"/></td></tr>
         <?php    }  ?>
            
            </tbody></table> 
            </div>
            <a class="right" target="_blank" href="<?php echo get_edit_post_link($wcorder->get_order_number()); ?>">View order</a>
           <?php   }else{echo "Not available.";}?>
         
         </td>
         </tr>
         <?php } else{ ?>
          <tr><td><b class="red"><?php echo __('Order not found','bs_anet_wc'); ?></b></td><td></td></tr>
         <?php } ?>
         
      </table>
         

        
      </td>
      </tr>

      <tr>
      <td valign="top">
      <table class="billing_desc">
      <tr>
      
      <td><h3><?php echo __('Billing Address','bs_anet_wc'); ?>:</h3></td>
      <td></td>
   </tr>
   <?php if($billTo){ ?>
   <tr>
      <td><?php echo __('Name','bs_anet_wc'); ?>:</td>
      <td><?php echo $billTo->getFirstName().' '.$billTo->getLastName();?></td>
   </tr>
   <tr>
      <td><?php echo __('Address','bs_anet_wc'); ?>:</td>
      <td><?php echo  $billTo->getAddress(); ?></td>
   </tr>

   <tr>
      <td><?php echo __('City','bs_anet_wc'); ?>:</td>
      <td><?php echo  $billTo->getCity(); ?></td>
   </tr>

   <tr>
      <td><?php echo __('Zip','bs_anet_wc'); ?>:</td>
      <td><?php echo  $billTo->getZip(); ?></td>
   </tr>

   <tr>
      <td><?php echo __('State','bs_anet_wc'); ?>:</td>
      <td><?php echo  $billTo->getState(); ?></td>
   </tr>

   <tr>
      <td><?php echo __('Country','bs_anet_wc'); ?>:</td>
      <td><?php echo  $billTo->getCountry(); ?></td>
   </tr>
   <?php } else { ?> 
      <tr>
            <td><b class="red"><?php echo __('Not found','bs_anet_wc'); ?></b></td>
            <td></td>
         </tr> 

   <?php } ?>
      </table>
      
      </td>
      
      <td valign="top">

       <table class="shipping_desc">
      <tr>
      
            <td><h3><?php echo __('Shipment Address','bs_anet_wc'); ?>:</h3></td>
            <td></td>
         </tr>
         <?php if($shipTo){ ?>
         <tr>
            <td><?php echo __('Name','bs_anet_wc'); ?>:</td>
            <td><?php  echo $shipTo->getFirstName().' '.$shipTo->getLastName();?></td>
         </tr>
         <tr>
            <td><?php echo __('Address','bs_anet_wc'); ?>:</td>
            <td><?php echo  $shipTo->getAddress(); ?></td>
         </tr>

         <tr>
            <td><?php echo __('City','bs_anet_wc'); ?>:</td>
            <td><?php echo  $shipTo->getCity(); ?></td>
         </tr>

         <tr>
            <td><?php echo __('Zip','bs_anet_wc'); ?>:</td>
            <td><?php echo  $shipTo->getZip(); ?></td>
         </tr>

         <tr>
            <td><?php echo __('State','bs_anet_wc'); ?>:</td>
            <td><?php echo  $shipTo->getState(); ?></td>
         </tr>

         <tr>
            <td><?php echo __('Country','bs_anet_wc'); ?>:</td>
            <td><?php echo  $shipTo->getCountry(); ?></td>
         </tr>
         <?php } else { ?>
            <tr>
            <td><b class="red"><?php echo __('Not found','bs_anet_wc'); ?></b></td>
            <td></td>
         </tr> 
         <?php } ?>
        
      </table> 

      </td>
      </tr>
      

   </table>
</div>
   <?php } elseif($return['state']==0 && $return['data']) { ?>
   <div class="anet_gateway_message">
      <p><?php echo $return['data']; ?></p>
   </div>
   <?php } ?>
</div>
<script>jQuery('.has_bs_anet_tip').tooltipster();</script>