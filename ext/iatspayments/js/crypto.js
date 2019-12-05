/* 
 * custom js so we can use the FAPS cryptojs script
 *
 */

/*jslint indent: 2 */
/*global CRM, ts */

cj(function ($) {
  'use strict';
  if (0 < $('#iats-faps-ach-extra').length) {
    /* move my helpful cheque image + instructions to the top of the section */
    $('.direct_debit_info-section').prepend($('#iats-faps-ach-extra'));
  }

  var isRecur = $('#is_recur, #auto_renew').prop('checked');
  generateFirstpayIframe(isRecur);
  $('#is_recur, #auto_renew').click(function() {
    // remove any existing iframe and message handlers first
    $('#firstpay-iframe').remove();
    if (window.addEventListener) {
      window.removeEventListener("message",fapsIframeMessage);
    }
    else {
      window.detachEvent("onmessage", fapsIframeMessage);
    }
    isRecur = this.checked;
    generateFirstpayIframe(isRecur);
  });

  function generateFirstpayIframe(isRecur) {
    // we get iatsSettings from global scope
    // we have four potential "transaction types" that generate different
    // iframes
    if (iatsSettings.paymentInstrumentId == '1') {
      var transactionType = isRecur ? 'Auth' : 'Sale';
    }
    else {
      var transactionType = isRecur ? 'Vault' : 'AchDebit';
    }
    // console.log(transactionType);
    // generate an iframe below the cryptgram field
    $('<iframe>', {
      'src': iatsSettings.iframe_src,
      'id':  'firstpay-iframe',
      'data-transcenter-id': iatsSettings.transcenterId,
      'data-processor-id': iatsSettings.processorId,
      'data-transaction-type': transactionType,
      'data-manual-submit': 'false',
      'frameborder': 0,
      'style': 'width: 100%',
      'scrolling': 'no'
    }).insertAfter('#payment_information .billing_mode-group .cryptogram-section');
    // load the cryptojs script and install event listeners - this needs to happen
    // after the iframe is generated.
    $.getScript(iatsSettings.cryptojs,  function(data, textStatus, jqxhr) {
      // handle "firstpay" messages (from iframes), supporting multiple javascript versions
      if (window.addEventListener) {
        window.addEventListener("message",fapsIframeMessage, false);
      }
      else {
        window.attachEvent("onmessage", fapsIframeMessage);
      }
    });
  }

});


var fapsIframeMessage = function (event) {
  if (event.data.firstpay) {
    // console.log(event.data);
    switch(event.data.type) {
      case 'newCryptogram':
        // assign the cryptogram value into my special field
        var newCryptogram = event.data.cryptogram;
        // console.log(newCryptogram);
        cj('#cryptogram').val(newCryptogram);
        break;
      case 'generatingCryptogram':
        // prevent submission before it's done ?
        break;
      case 'generatingCryptogramFinished':
        // can be ignored?
        break;
      case 'cryptogramFailed':
        // alert user
        cj('#cryptogram').crmError(ts(event.data.message));
        break;
    }
  }
}
