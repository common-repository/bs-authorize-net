<?php 
$report =  BS_Anet_WC_Reports::getInstance();
$helper = BS_Anet_WC_helper::getInstance();
$batchList = $report->getTransactionsbatchList();
$batchStartdate = date('Y-m-d', strtotime(date('Y-m-d') . ' -29 day')) ;
$batchEnddate = date('Y-m-d');

if(isset($_POST['batchrangev']))
    {
        
        $dinput = sanitize_text_field($_POST['batchrangev']);

        $dinput = explode('*',$dinput);

        $batchStartdate = $dinput[0];

        $batchEnddate = $dinput[1];

       
    }

?>


<form class="settletbatch" style="display:none;" method="post" action="?page=merchantdashboard&tab=setteledtransactions">
    <div class="tablenav top">
        <div class="alignleft actions bulkactions">
            <label for="bulk-action-selector-top" class=""><?php echo __('Select Transactions Batch Range','bs_anet_wc');?></label>
            <input type="text" id="batchrange" name="batchrange" />
            <input type="hidden" id="batchrangev" value="<?php echo $batchStartdate.'*'.$batchEnddate; ?>" name="batchrangev" />
            <input type="submit" id="setbatch" class="button action" value="Load batch">
        </div>
    </div>

    <?php if($batchList['state']!=0 && $batchList['data']) { ?>
    <h2>Batch list</h2>
    <table class="wp-list-table widefat fixed batch-list-table" border="0">
        <thead>
            <tr>
                <th class="column-primary batch-col-sm">
                    <?php echo __('Batch ID','bs_anet_wc');?>
                </th>
                <th scope="col" class="column-primary batch-col-lg">
                    <?php echo __('Settlement Date(UTC)','bs_anet_wc');?>
                </th>
                <th scope="col" class="column-primary batch-col-lg">
                    <?php echo __('Settlement Date','bs_anet_wc');?>
                </th>
                <th scope="col" class="column-primary batch-col-md">
                    <?php echo __('Settlement State','bs_anet_wc');?>
                </th>
                <th scope="col" class="column-primary batch-col-md">
                    <?php echo __('Batch Product','bs_anet_wc');?>
                </th>
                <th scope="col" class="column-primary batch-col-md">
                    <?php echo __('Net earnings','bs_anet_wc');?>
                </th>
                <th scope="col" class="column-primary">
                    <?php echo __('Actions','bs_anet_wc');?>
                </th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($batchList['data'] as $batchData) {
			$in = $batchData["total"];
			$out = $batchData["refunds"];
			
			$profit = ($in-$out);
			?>
            <tr id="<?php echo $batchData['batch_id']; ?>" class="batchRow">

                <td class="column-primary ">
                    <?php echo $batchData['batch_id']; ?>
                </td>
                <td class="column-primary">
                    <?php echo $batchData['sutc'];  ?>
                </td>
                <td class="column-primary">
                    <?php echo $batchData['bsloc'];  ?>
                </td>
                <td class="column-primary"><span class="highlight-bst"><?php echo $batchData['state'];  ?></span></td>
                <td class="column-primary">
                    <?php echo $batchData['batch_product'];  ?>
                </td>
                <td class="column-primary has_bs_anet_tip <?php echo $profit>=0?'batch-profit':'batch-loss'; ?> net-earn"><b title="Net earnings are calculated as Total earnings-Total refunds(<?php echo $in.' - '.$out.' = '.$profit;?>)"><?php echo $profit.' '.$batchList['currency'];  ?><span><i class="fa fa-<?php echo $profit>0?'caret-up':'caret-down';?>"></i></span></b></td>
                <td class="column-primary"><a class="view-transactions viewstat has_bs_anet_tip" title="View batch statistics and earnings" href="#stat-<?php echo $batchData['batch_id']; ?>" rel="modal:open"><i class="fas fa-chart-bar"></i>Batch Stats</a>
                    <a title="<?php echo __(" View Transactions ", 'bs_anet_wc'); ?>" href="<?php echo admin_url( 'admin-ajax.php' ); ?>?action=anet_setteled_transactions&batch_id=<?php echo $batchData['batch_id']; ?>&remote=0" rel="modal:open" aria-disabled="false" class="view-transactions"><i class="fa fa-eye"></i>View Transactions</a></td>
                <!-- stats -->
                <div id="stat-<?php echo $batchData['batch_id']; ?>" class="modal">

                    <div class="wrapchart">
                        <h1 class="wp-heading-inline wp-heading-modal">Batch Id:
                            <?php echo $batchData['batch_id']; ?>
                        </h1>

                        <div class="p-methods-container-earning">
                            <h1 class="wp-heading-inline wp-heading-modal">Earnings</h1>
                            <div class="payment-stats " ><canvas id="payment-stats-ear-<?php echo $batchData['batch_id']; ?>"></canvas></div>
                            <div class="batch-summary ">

                                <p>
                                    <h3>
                                        <?php echo __('Earnings Summary','bs_anet_wc');?>
                                    </h3>
                                </p>
                                <p class="summary-row">
                                    <?php echo __('Total Earnings','bs_anet_wc');?>:<b><?php echo $batchData["total"].$batchList['currency']; ?></b></p>
                                <p class="summary-row">
                                    <?php echo __('Total charge counts','bs_anet_wc');?>:<b><?php echo $batchData["cattempts"]; ?></b></p>
                                <p class="summary-row">
                                    <?php echo __('Number of declines','bs_anet_wc');?>:<b><?php echo $batchData["declines"]; ?></b></p>
                                <p class="summary-row">
                                    <?php echo __('Number of transactions voided','bs_anet_wc');?>:<b><?php echo $batchData["voids"]; ?></b></p>
                                <p class="summary-row">
                                    <?php echo __('Number of errors encountered','bs_anet_wc');?>:<b><?php echo $batchData["errors"]; ?></b></p>
                            </div>
                            <script>
                                <?php  printTransactionChart("payment-stats-ear-",$batchList['merchantInfo'],$batchData,$batchList['currency'],'chargeAmount'); ?>
                            </script>
                        </div>
                        <hr>
                        <div class="p-methods-container-refunds">
                            <h1 class="wp-heading-ref">Refunds</h1>
                            <div class="payment-stats " ><canvas id="payment-stats-ref-<?php echo $batchData['batch_id']; ?>" ></canvas></div>
                            <div class="batch-summary ">

                                <p>
                                    <h3>
                                        <?php echo __('Refunds Summary','bs_anet_wc');?>
                                    </h3>
                                </p>
                                <p class="summary-row">
                                    <?php echo __('Total Refunds','bs_anet_wc');?>:<b><?php echo $batchData["refunds"].$batchList['currency']; ?></b></p>
                                <p class="summary-row">
                                    <?php echo __('Total refund counts','bs_anet_wc');?>:<b><?php echo $batchData["rattempts"]; ?></b></p>

                            </div>
                            <script>
                                <?php printTransactionChart("payment-stats-ref-",$batchList['merchantInfo'],$batchData,$batchList['currency'],'refundAmount'); ?>
                            </script>
                        </div>

                    </div>
                    <!-- stats end -->
            </tr>
            <?php } ?>
        </tbody>
        <tfoot>

        </tfoot>
    </table>

    <div class="tablenav bottom">

        <div class="alignleft actions">
        </div>
        <div class="tablenav-pages one-page"><span class="displaying-num"><?php echo count($batchList['data']); ?> items</span>
            <br class="clear">
        </div>

        <?php } elseif($batchList['state']==0 && $batchList['data']) { ?>
        <div class="anet_gateway_message">
            <p>
                <?php echo $batchList['data']; ?>
            </p>
        </div>

        <?php } ?>



</form>

