<?php 
$report =  BS_Anet_WC_Reports::getInstance();
$helper = BS_Anet_WC_helper::getInstance();
$unseteledtrans = $report->anet_unsetteled_transactions();
$limit = isset($_GET['limit'])?(int)sanitize_text_field($_GET['limit']): 50;
$offset = isset($_GET['offset'])?(int)sanitize_text_field($_GET['offset']):1;
/* $dir = isset($_GET['dir'])?sanitize_text_field($_GET['dir']):'n';
$offset = $dir=='n'?($offset+1):($offset-1); */
$next = admin_url('admin.php').'?page=merchantdashboard&tab=unsetteledtransactions&limit='.$limit.'&offset='.($offset+1);
$prev = admin_url('admin.php').'?page=merchantdashboard&tab=unsetteledtransactions&limit='.$limit.'&offset='.($offset-1);


$hasdata = $unseteledtrans['state']!=0 && $unseteledtrans['data']?true:false;

?>



<form method="post" action="?page=merchantdashboard">
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">

            <h2>
                <?php echo __('Unsettled Transactions','bs_anet_wc');?>
            </h2>
        </div>

        <div class="alignright actions bulkactions">
            <div id="pagination"></div>
            <input type="hidden" id="offset" value="1">
            <input type="hidden" id="limit" value="2">
            <?php if($offset>=$limit){ ?>
            <a href="<?php echo $prev; ?>" id="prevbtn" class="view-transactions transbtn-md left"><i class="icon-arrow-circle-o-left"></i>Load prev <?php echo $limit; ?></a>
            <?php } if($hasdata && $unseteledtrans['total']>=$limit ){ ?>
            <a href="<?php echo $next; ?>" id="nextbtn" class="view-transactions transbtn-md left">Load next <?php echo $limit; ?><i class="icon-arrow-circle-o-right"></i></a>
            <?php } ?>

        </div>

    </div>

    <?php if($hasdata ) { ?>

    <div class="anet-transactions-list anet-unsetteled-transactions-list">
        <table class="wp-list-table widefat fixed batch-list-table" border="0">
            <thead>
                <tr>

                    <th scope="col" class="column-primary batch-col-md">
                        <?php echo __('Transaction ID','bs_anet_wc');?>
                    </th>
                    <th scope="col" class="column-primary ">
                        <?php echo __('Transaction Date(UTC)','bs_anet_wc');?>
                    </th>
                    <th scope="col" class="column-primary ">
                        <?php echo __('Transaction Date','bs_anet_wc');?>
                    </th>
                    <th scope="col" class="column-primary">
                        <?php echo __('Transaction status','bs_anet_wc');?>
                    </th>
                    <th scope="col" class="column-primary">
                        <?php echo __('Customer','bs_anet_wc');?>
                    </th>
                    <th scope="col" class="column-primary">
                        <?php echo __('Account type','bs_anet_wc');?>
                    </th>
                    <th scope="col" class="column-primary">
                        <?php echo __('Amount','bs_anet_wc');?>
                    </th>
                    <th scope="col" class="column-primary">
                        <?php echo __('Actions','bs_anet_wc');?>
                    </th>
                </tr>
            </thead>

            <tbody id="transaction-rows">

                <?php foreach($unseteledtrans['data'] as $unseteledtran) {  ?>


                <tr id="<?php echo $unseteledtran['id']; ?>" class="transactRow">
                    <td class="column-primary">
                        <?php echo $unseteledtran['id']; ?>
                    </td>
                    <td class="column-primary">
                        <?php echo $unseteledtran['submittedOnU']; ?>
                    </td>
                    <td class="column-primary">
                        <?php echo $unseteledtran['submittedOnL']; ?>
                    </td>
                    <td class="column-primary"><span class="tstate <?php echo $unseteledtran['status']; ?>"><?php echo $unseteledtran['status']; ?></span></td>
                    <td class="column-primary">
                        <?php echo $unseteledtran['name']; ?>
                    </td>
                    <td class="column-primary">
                        <?php echo $unseteledtran['account_type']; ?>
                    </td>
                    <td class="column-primary">
                        <?php echo $unseteledtran['amount']; ?>
                    </td>
                    <td class="column-primary"><a data-block=".anet-unsetteled-transactions-list" data-href="<?php echo admin_url( 'admin-ajax.php' ); ?>?action=anet_transaction_details&transaction_id=<?php echo $unseteledtran['id']; ?>" class="view-transactions load-transaction">View Details</a></td>
                </tr>
                <?php  }?>





            </tbody>
            <tfoot>

            </tfoot>
        </table>
    </div>

    <div class="tablenav bottom">


        <div class="tablenav-pages one-page">Displaying &nbsp;<span id="transact-disp-num" class="displaying-num"><?php echo $unseteledtrans['total'] ?></span>&nbsp; items</div>
        <br class="clear">
    </div>


    <?php } elseif($unseteledtrans['state']==0 && $unseteledtrans['data']) { ?>
    <div class="anet_gateway_message">
        <p>
            <?php echo $unseteledtrans['data']; ?>
        </p>
    </div>

    <?php } ?>


</form>