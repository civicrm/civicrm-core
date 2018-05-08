/* Custom cleanup/validation of Canadian ACH/EFT */
/*jslint indent: 2 */
/*global CRM, ts */
function iatsACHEFTca() {
  cj(function ($) {
    'use strict';
    function onlyNumbers(jq) {
      var myVal = jq.val();
      jq.val(myVal.replace(/\D/g,''));
      return jq.val().length;
    }
    function iatsSetBankIdenficationNumber() {
      var bin = $('#cad_bank_number').val() + $('#cad_transit_number').val();
      // console.log('bin: '+bin);
      $('#bank_identification_number').val(bin);
    }
    /* hide the bank identiication number field */
    $('.bank_identification_number-section').hide();
    iatsSetBankIdenficationNumber();
    $('#bank_account_number').blur(function(eventObj) {
      onlyNumbers($(this));
    });
    $('#cad_bank_number').blur(function(eventObj) {
      var myCount = onlyNumbers($(this));
      if (myCount != 3) {
        $(this).crmError(ts('Your Bank Number requires three digits, use a leading "0" if necessary')); 
      }
      switch($(this).val()) {
        case '001': $('#bank_name').val('Bank of Montreal'); break;
        case '002': $('#bank_name').val('Bank of Nova Scotia'); break;
        case '003': $('#bank_name').val('Royal Bank of Canada'); break;
        case '004': $('#bank_name').val('Toronto Dominion Bank'); break;
        case '006': $('#bank_name').val('National Bank of Canada'); break;
        case '010': $('#bank_name').val('Canadian Imperial Bank of Commerce'); break;
        case '016': $('#bank_name').val('HSBC Canada'); break;
        case '030': $('#bank_name').val('Canadian Western Bank'); break;
        case '326': $('#bank_name').val('President\'s Choice Financial'); break;

        case '839': // Credit Union Heritage (Nova Scotia)
        case '879': // Credit Union Central of Manitoba
        case '889': // Credit Union Central of Saskatchewan
        case '899': // Credit Union Central Alberta
        case '809': // Credit Union British Columbia
          $('#bank_name').val('Credit Union'); break;
        
        default: $('#bank_name').val(''); break;
      }
      iatsSetBankIdenficationNumber();
    });
    $('#cad_transit_number').blur(function(eventObj) {
      var myCount = onlyNumbers($(this));
      if (myCount != 5) {
        $(this).crmError(ts('Your Bank Transit Number requires exactly five digits')); 
      }
      iatsSetBankIdenficationNumber();
    });
  });
}
