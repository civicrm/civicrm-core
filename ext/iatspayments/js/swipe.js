/*jslint indent: 2 */
/*global CRM, ts */

CRM.$(function ($) {
  'use strict';

  function iatsSetCreditCardNumber() {
    var bin = $('#encrypted_credit_card_number').val();
    // console.log('bin: '+bin);
    if (bin.charAt(0) == '0') {
      /* if 0 -> IDTech -> prefix = 00|@| */
      var withprefix = "00|@|"+bin;
      $('#credit_card_number').val(withprefix);
      // console.log('withprefix: '+withprefix);
    }
    if (bin.charAt(0) == '%') {
      /* if % -> MagTek -> prefix = 02|@| */
      var withprefix = "02|@|"+bin;
      $('#credit_card_number').val(withprefix);
      // console.log('withprefix: '+withprefix);
    }
  }
  
  function clearField() {
    var field = $('#encrypted_credit_card_number').val();
    /* console.log('field: '+field); */
    if (field == ts('Click here - then swipe.')) {
      $('#encrypted_credit_card_number').val('');
    }
  }
 
  if (0 < $('#iats-swipe').length) {
    /* move my custom fields up where they belong */
    $('#payment_information .credit_card_info-group').prepend($('#iats-swipe'));
    /* hide the number credit card number field  */
    $('.credit_card_number-section').hide();
    /* hide some ghost fields from a bad template on the front end form */
    $('.-section').hide();  
    iatsSetCreditCardNumber();
    var defaultValue = ts('Click here - then swipe.');
    $('#encrypted_credit_card_number').val(defaultValue).focus(clearField).blur(iatsSetCreditCardNumber);
  }
});
