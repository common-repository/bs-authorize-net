jconfirm.defaults = {
  boxWidth: '30%',
  useBootstrap: false,
  animation: 'none'
};






  
    function dispTrans(data)
          {
          try {
            var transactions = JSON.parse(data);
            var offset = parseInt(jQuery("#offset").val());
            var limit = parseInt(jQuery("#limit").val());
            
            var container = jQuery("#transaction-rows");
            let rows = "";
           
            transactions.forEach(element => {
              if(element.id)
              {
                var rootPath = batchrange.admin_url+'?action=anet_transaction_details&transaction_id='+element.id;
            rows+='<tr id="'+element.id+'" class="transactRow"><td class="column-primary">'+element.id+'</td><td class="column-primary">'+element.submittedOnU+'</td><td class="column-primary">'+element.submittedOnL+'</td><td class="column-primary"><span class="highlight-bst">'+element.status+'</span></td><td class="column-primary">'+element.name+'</td><td class="column-primary">'+element.account_type+'</td><td class="column-primary">'+element.amount+'</td><td class="column-primary"><a data-block=".anet-transactions-list-setteled" onclick="loadTransaction(\'.anet-transactions-list-setteled\',\''+rootPath+'\')" class="view-transactions load-transaction">View Details</a></td></tr>';
          
              }
              container.empty();
              container.append(rows);

              
            });
            
            jQuery("#transact-disp-num").text(transactions.length);
            } catch(e) {
            aclert('Sorry something went wrong,Please try again later.');
        }
          }

          function loadnext(e,me,dir)
          {
            e.preventDefault();
            let elem = jQuery(me);
            let batch_id = parseInt(jQuery("#batch_id").val());
            var offsetCont = jQuery("#offset");
            var limitCont = jQuery("#limit");
            var prevbtn   = jQuery("#prevbtn");
            var nextbtn   = jQuery("#nextbtn");

            

           var offset = dir=='n'?(parseInt(offsetCont.val())+1):(parseInt(offsetCont.val())-1);
           var limit  = parseInt(limitCont.val());
          
          
            var path = batchrange.admin_url+'?action=anet_setteled_transactions&batch_id='+batch_id+'&limit='+limit+'&offset='+offset+'&remote=1';

            sendReq(path,(data)=>{

              offsetCont.val(offset);
              (offset>=limit)?prevbtn.show():prevbtn.hide();
              dispTrans(JSON.stringify(data.data));

            },elem)
            
            

          }

         
          //nice alert
          function aclert(msg)
          {
            jQuery.alert({
              title: 'Alert!',
              content: msg,
          });
          }

          //spinkit spinner
          function loading(state)
          {
            var spinnerHtml ='<div class="modal-spinner" style="display: block;"><div class="rect1"></div><div class="rect2"></div><div class="rect3"></div><div class="rect4"></div></div>';
            var spinner = jQuery('.modal-spinner');
            spinner.remove();
            if(state)
            jQuery('body').append(spinnerHtml);
            
          }

          function voidtransact(e,me,tid,oid,text)
          {
          
            text = jQuery("#void_msg_"+tid).html();

            confirmExt('Void transaction('+tid+')',text,'Void','cancel',(context)=>{
             var elem = jQuery(me); 
          
                 
             var path = batchrange.admin_url+'?action=anet_transaction_void&tid='+tid+'&o='+oid;

            sendReq(path,(data)=>{
              let text = '<p>Transaction successfully voided</p><p>Transaction response code:'+data.data.transaction_resp_code+'</p><p>Void transaction success auth code:'+data.data.void_transaction_success_auth_code+'</p><p>Void transaction id:'+data.data.void_transaction_success_trans_id+'</p><p>Code:'+data.data.code+'</p><p>Description:'+data.data.description+'</p>';
              
              success(text,true);
                
             },elem,"#trans_desc_"+tid);

             

            })
          }


          function confirmExt(title,content,tsub,tcanc,submit,ready)
          {

           
           var confirm =  jQuery.confirm({
           title: title,
            content:content, 
  buttons: {
      formSubmit: {
          text: tsub?tsub:'Ok',
          btnClass: 'btn-blue',
          action: function () {
            if(submit)
            submit(this);
          }
      },
      cancelSubmit: {
        text: tcanc?tcanc:'Cancel',
        btnClass: 'btn-red',
        action: function () {}

      },
  },
  onContentReady: function () {if(ready)ready();}
});
          }

          function success(content,reload)
          {
          jQuery.confirm({
           title: 'Success',
            content:content, 
  buttons: {
      formSubmit: {
          text: 'Ok',
          btnClass: 'btn-green',
          action: function () {
            if(reload)
            location.reload();
          }
      }
}
          })
          }


          function sendReq(path,cb,elem,block)
          {
            elem.attr('disabled','disabled');
            blockUi(block,true);

            path = path+'&nonce='+batchrange.ajax_nonce;
            jQuery.post(path,(data)=>{
              elem.removeAttr('disabled');
              blockUi(block,false);
                 try{
                   var pdata = JSON.parse(data);

                   if(pdata.state) 
                   {
                     cb(pdata);
                   }
                   else
                   {
                     aclert(pdata.data);
                   }
                 }
                 catch(e)
                 {
                  
                   aclert('Sorry something went wrong,please try again later.');
                 } 
            }) 
          }

          function blockUi(elem,block)
          {
            
            let ele = jQuery(elem);
            let blockParams = {
              message: null,
              overlayCSS: {
                background: '#fff',
                opacity: 0.6
              }
            };
        
            if (block)
            ele.block(blockParams);
            else
            ele.unblock();

          }
         
          function transactDetails()
          {
            jQuery('#wc-order-authnet-trans-details').on('click',function(e){ 
              e.preventDefault();
              {
               blockUi('#order_data',true);
                let tid  = jQuery(this).attr('data-id');
                let rootPath = batchrange.admin_url+'?action=anet_transaction_details&transaction_id='+tid;
                jQuery.post(rootPath,function(data){
                 blockUi('#order_data',false);
                 jQuery('<div class="modal">'+data+'</div>').appendTo('body').modal();
                }) 
              }

            })
          }

          function refundTransactStat()
          {
            jQuery('.order-details-ref-trans').on('click',function(e){
               e.preventDefault();
               {
                let tid  = jQuery(this).attr('data-id');
                let rootPath = batchrange.admin_url+'?action=anet_transaction_details&type=refund&transaction_id='+tid;
                loadTransaction('#woocommerce-order-notes',rootPath); 
               }
            });
          }

          function getTransaction()
          {
            jQuery('.load-transaction').on('click',function(e){
            e.preventDefault();
            let path = jQuery(this).attr('data-href');
            let blockElem = jQuery(this).attr('data-block');
            if(path && blockElem)
            {
              loadTransaction(blockElem,path);
            }
            
            })
          }

          function loadTransaction(blockElem,path)
          {
            blockUi(blockElem,true);
                 
                 jQuery.post(path,function(data){
                  blockUi(blockElem,false);
                  jQuery('<div class="modal">'+data+'</div>').appendTo('body').modal();
                 }) 
          }

          function fdstransactAction(e,me,tid,type)
          {
            e.preventDefault();
            let  text = type=='A'?'Are you sure you want to approve this transaction?':(type=='D'?'Are you sure you want to decline this transaction?':'');
            let header = type=='A'?'Approve transaction('+tid+')':(type=='D'?'Decline transaction('+tid+')':'');

             confirmExt(header,text,'yes','cancel',(context)=>{
              
              var elem = jQuery(me); 
           
                  
            
              let path = batchrange.admin_url+'?action=anet_transaction_fdsaction&transaction_id='+tid+'&fds='+type;
 
             sendReq(path,(data)=>{
               let text = '<p>Transaction successfully '+data.data.action+'</p><p>Transaction response code:'+data.data.resp_code+'</p><p>Transaction success auth code:'+data.data.auth_code+'</p><p>Void transaction id:'+data.data.trans_id+'</p><p>Code:'+data.data.code+'</p><p>Description:'+data.data.description+'</p>';
               
               success(text,true);
                 
              },elem,"#trans_desc_"+tid);
 
              
 
             })

          }

          jQuery(function() {
    
            jQuery('input[name="batchrange"]').daterangepicker({
                startDate: batchrange.startdate,
                endDate: batchrange.enddate,
              opens: 'right',
              "maxSpan": {
                "days": 30
            },
            "locale": {
                "format": "MM/DD/YYYY"
            },
            "showDropdowns": true,
            }, function(start, end, label) {
              jQuery("#batchrangev").val(start.format('YYYY-MM-DD') + '*' + end.format('YYYY-MM-DD'));
            });
        
            jQuery('[title]').tooltipster();
        
            refundTransactStat();
            transactDetails();
            getTransaction();
          });   
       


 


