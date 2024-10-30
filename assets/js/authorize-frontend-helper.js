var Helper = function() {


    var form = jQuery("form[name='checkout']");

    var body = jQuery('body');

    var self = this;

    var gateway;

    var authdata = {
        clientKey: paramsgw.k,
        apiLoginID: paramsgw.l
    };




    this.init = function() {



        if (!is_event_bound('checkout_place_order', 'token_request', form))
            form.on('checkout_place_order', token_request);
        if (!is_event_bound('updated_checkout', 'update_actions', body))
            body.on('updated_checkout', update_actions);
        if (!is_event_bound('checkout_error', 'handle_checkout_error', body))
            body.on('checkout_error', handle_checkout_error);

        toggleFields(true);

    }

    var detectGateway = function() {

        var selector = jQuery('input[name="payment_method"]');
        if (!is_event_bound('change', 'detectGateway', selector))
            selector.on('change', detectGateway);

        gateway = jQuery("input[name='payment_method']:checked").val();

        if (gateway == paramsgw.echeck || gateway == paramsgw.cc) {

            self.init();
        } else {

            destroy();
        }

    }

    var handle_checkout_error = function() {
        if (gateway == paramsgw.echeck || gateway == paramsgw.cc) {
            self.init();
        }
    }
    var is_event_bound = function(eventname, handler, element) {
        let $return = false;
        let events_bounded = jQuery._data(element[0], "events");
        if (events_bounded) {
            let all_events = events_bounded[eventname];


            for (var e in events_bounded) {
                var item = events_bounded[e][0];

                if (item.handler.name == handler) {
                    $return = true;
                    break;
                }
            }
        }
        return $return;

    }
    var prepare_card_data = function() {
        var cdata = {
            cardNumber: jQuery('#' + paramsgw.cc + '-card-number').val() ? jQuery('#' + paramsgw.cc + '-card-number').val().replace(/ /g, '') : "",
            month: jQuery('#' + paramsgw.cc + '-card-expiry').val() ? jQuery('#' + paramsgw.cc + '-card-expiry').val().split('/')[0].replace(/ /g, '') : "",
            year: jQuery('#' + paramsgw.cc + '-card-expiry').val() ? jQuery('#' + paramsgw.cc + '-card-expiry').val().split('/')[1].replace(/ /g, '') : "",
            cardCode: jQuery('#' + paramsgw.cc + '-card-cvc').val() ? jQuery('#' + paramsgw.cc + '-card-cvc').val().replace(/ /g, '') : ""

        }

        return cdata;
    }

    var prepare_echeck_data = function() {

        return {
            accountNumber: jQuery('#' + paramsgw.echeck + '-account-number').val() ? jQuery('#' + paramsgw.echeck + '-account-number').val().replace(/ /g, '') : "",
            routingNumber: jQuery('#' + paramsgw.echeck + '-routing-number').val() ? jQuery('#' + paramsgw.echeck + '-routing-number').val().replace(/ /g, '') : "",
            nameOnAccount: jQuery('#' + paramsgw.echeck + '-account-name').val() ? jQuery('#' + paramsgw.echeck + '-account-name').val().replace(/ /g, '') : "",
            accountType: jQuery('#' + paramsgw.echeck + '-account-type').val() ? jQuery('#' + paramsgw.echeck + '-account-type').val().replace(/ /g, '') : "",
            bankName: jQuery('#' + paramsgw.echeck + '-bank-name').val() ? jQuery('#' + paramsgw.echeck + '-bank-name').val().replace(/ /g, '') : ""
        }
    }

    var token_request = function() {

        let $isTokenizeSelection = false;
        let $iscard = jQuery("input[value='" + paramsgw.cc + "']").is(':checked');
        let $isEcheck = jQuery("input[value='" + paramsgw.echeck + "']").is(':checked');
        let ccTokenName = 'wc-' + paramsgw.cc + '-payment-token';
        let echeckTokenName = 'wc-' + paramsgw.echeck + '-payment-token';
        let $ccToken = jQuery("input[name='" + ccTokenName + "']:checked");
        let $echeckToken = jQuery("input[name='" + echeckTokenName + "']:checked");
        let $tokenType = $iscard ? 'c' : ($isEcheck ? 'e' : null);


        if ($iscard && $ccToken.length && $ccToken.val() != 'new')
            $isTokenizeSelection = true;


        if ($isEcheck && $echeckToken.length && $echeckToken.val() != 'new')
            $isTokenizeSelection = true;



        if (($isEcheck || $iscard) && !$isTokenizeSelection && $tokenType) {

            blockPMF(true);
            set_token($tokenType, (data) => {


                if (data.messages.resultCode == 'Error') {
                    throwError(data.messages.message);
                    blockPMF(false);
                    return false;
                } else {

                    paymentFormUpdate(data.opaqueData);

                }

            });

            return false;

        } else {
            return true;
        }


    }


    var set_token = function(t, handler) {


        let secureData = {};
        let acc_data = t == 'c' ? secureData.cardData = prepare_card_data() : secureData.bankData = prepare_echeck_data();
        secureData.authData = authdata;

        validatePaymentdataAccept(secureData, (validatedata) => {
            if (validatedata.messages.resultCode === 'Success') {
                Accept.dispatchData(secureData, handler);
            } else {
                handler(validatedata);
            }

        });
    }


    var throwError = function(msgs) {

        let msgHtml = '<div class="woocommerce-NoticeGroup woocommerce-NoticeGroup-updateOrderReview"><ul class="woocommerce-error" role="alert">';
        msgs.forEach(element => {
            if (element.code.length) {
                msgHtml += '<li>' + element.text + '</li>';
            }

        });
        msgHtml += '</ul></div>';
        jQuery(".woocommerce-NoticeGroup").remove();
        jQuery('form[name="checkout"]').prepend(msgHtml);
        jQuery([document.documentElement, document.body]).animate({
            scrollTop: jQuery(".woocommerce-NoticeGroup").offset().top - 100
        }, 1000);


    }

    var blockPMF = function(block) {


        let blockparamsgw = {
            message: null,
            overlayCSS: {
                background: '#fff',
                opacity: 0.6
            }
        };

        if (block)
            form.block(blockparamsgw);
        else
            form.unblock();

    }

    var paymentFormUpdate = function(opaqueData) {


        document.getElementById("dataDescriptor").value = opaqueData.dataDescriptor;
        document.getElementById("dataValue").value = opaqueData.dataValue;
        form.off('checkout_place_order', token_request);

        form.submit();
    }

    var update_actions = function() {
        detectGateway();
    }

    var validatePaymentdataAccept = function(data, cb) {



        if (data.cardData) {
            let card = data.cardData;

            setValidationrequest('validatecard', {
                [paramsgw.cc + '-card-number']: card.cardNumber,
                [paramsgw.cc + '-card-cvc']: card.cardCode,
                [paramsgw.cc + '-card-expiry']: (card.month.length && card.year.length ? card.month + '/' + card.year : "")
            }, (data) => {
                cb(data);
            });

        } else if (data.bankData) {
            bank = data.bankData;

            setValidationrequest('validateecheck', {
                [paramsgw.echeck + '-account-number']: bank.accountNumber,
                [paramsgw.echeck + '-routing-number']: bank.routingNumber,
                [paramsgw.echeck + '-account-name']: bank.nameOnAccount,
                [paramsgw.echeck + '-bank-name']: bank.bankName,
                [paramsgw.echeck + '-account-type']: bank.accountType
            }, (data) => {
                cb(data);
            });


        }


    }

    var setValidationrequest = function(type, vdata, cb) {
        let $return = {
            messages: {
                resultCode: 'Success',
                message: []
            }
        }
        let path = paramsgw.ajaxpath + '?action=' + type + '&nonce=' + paramsgw.nonce + '&remote=1';
        jQuery.post(path, vdata, (data) => {
            try {
                var pdata = JSON.parse(data);

                if (!pdata.state) {
                    $return.messages.resultCode = 'Error';
                    pdata.messages.forEach((item, index) => {
                        $return.messages.message.push({
                            text: item,
                            code: 'ERR' + index
                        });
                    })
                }
                cb($return);
            } catch (e) {
                $return.messages.resultCode = 'Error';
                $return.messages.message.push({
                    text: 'Unknown error occured.',
                    code: 'ERRV'
                });
                cb($return);
            }
        })
    }



    var destroy = function() {

        if (is_event_bound('checkout_place_order', 'token_request', form))
            form.off('checkout_place_order', token_request);
        if (is_event_bound('updated_checkout', 'update_actions', body))
            form.off('updated_checkout', update_actions);

        if (is_event_bound('checkout_error', 'handle_checkout_error', body))
            body.off('checkout_error', handle_checkout_error);
        toggleFields(false);

    }

    var toggleFields = function(add) {

        var descriptor = jQuery("#dataDescriptor");
        var datavalue = jQuery("#dataValue");
        descriptor.val('');
        datavalue.val('');
        if (add && !descriptor.length && !datavalue.length) {

            form.append('<input type="hidden" name="dataDescriptor" id="dataDescriptor"/>');
            form.append('<input type="hidden" name="dataValue" id="dataValue"/>');
        } else if (!add) {

            descriptor.remove();
            datavalue.remove();
        }
    }
}

jQuery(function() {
    let helper = new Helper();
    helper.init();
})