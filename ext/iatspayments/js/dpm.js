/* 
 * custom js for direct post method 
 *
 * Intercept the normal submission of sensitive billing data,
 * submit it in the background via ajax
 * and include the results for parsing by the payment processor doDirectPayment script
 * while masking out the credit card information
 */

/*jslint indent: 2 */
/*global CRM, ts */

cj(function ($) {
  'use strict';

  // hide fields that are used for error display before validation
  $('#iats-dpm-continue .crm-error').hide();
  /* initiate an ajax submission of the billing dadta: check for required fields, then do a background POST */
  $('#crm-submit-buttons .crm-button input').click(function( defaultSubmitEvent ) {
    var inputButton = $(this);
    inputButton.val('Processing ...').prop('disabled',true);
    // reset the list of errors
    $('#iats-dpm-required').html('');
    // the payment information group is all required (except the middle name) but doesn't have the class marked
    $('#Main #payment_information input:visible, #Main #payment_information select:visible, #Main input.required:visible').each(function() {
      // console.log(this.value.length);
      if (0 == this.value.length && this.id != 'billing_middle_name') {
        if ('installments' == this.id) { // todo: check out other exceptions
          var myLabel = 'Installments';
        }
        else {
          var myLabel = $.trim($(this).parent('.content').prev('.label').find('label').text().replace('*',''));
        }
        if (myLabel.length > 0) { // skip any weird hidden fields (e.g. select2-offscreen class)
          $('#iats-dpm-required').append('<li>' + myLabel + ' is a required field.</li>');
        }
      }
    })
    // if all pre-validation requirements are met, I can do the sycronous POST to try a submission
    // TODO: javascript version of Luhn, perhaps http://jquerycreditcardvalidator.com/
    // console.log($('#iats-dpm-required'));
    if (0 == $('#iats-dpm-required').html().length) {
      var dpmURL = (typeof CRM.vars.iatspayments != 'undefined') ? CRM.vars.iatspayments.dpmURL : CRM.iatspayments.dpmURL;
      console.log(dpmURL);
      var dpmPOST = {};
      dpmPOST.IATS_DPM_ProcessID = 'PA0940D765F2BD67BD97B82EFAA4D72BE9';
      dpmPOST.IATS_DPM_FirstName = $('#billing_first_name').val();
      dpmPOST.IATS_DPM_LastName = $('#billing_last_name').val();
      dpmPOST.IATS_DPM_Address = $('input[name|="billing_street_address"]').val();
      dpmPOST.IATS_DPM_City = $('input[name|="billing_city"]').val();
      dpmPOST.IATS_DPM_Province = $('select[name|="billing_state_province_id"]').find('selected').text();
      dpmPOST.IATS_DPM_ZipCode = $('input[name|="billing_postal_code"]').val();
      dpmPOST.IATS_DPM_Country = $('select[name|="billing_country_id"]').find('selected').text();
      dpmPOST.IATS_DPM_Email = $('input[name|="email"]').val();
      
      dpmPOST.IATS_DPM_AccountNumber = $('#credit_card_number').val();
      var Month = $('#credit_card_exp_date_M').val();
      var Year = $('#credit_card_exp_date_Y').val();
      dpmPOST.IATS_DPM_ExpiryDate = Month+'/'+Year.substr(2);
      dpmPOST.IATS_DPM_CVV2 = $('#cvv2').val();
      // todo: translate mop values 
      dpmPOST.IATS_DPM_MOP = $('#credit_card_type').val();
      dpmPOST.IATS_DPM_Amount = $('.contribution_amount-section input:checked').prop('data-amount');
      console.log(dpmPOST);
      /* this ajax call has to be sychronous! */
      $.ajax({
        type: 'POST',
        url: dpmURL,
        data: dpmPOST,
        dataType: 'json', 
        async: false,
        success: function(result) {
          console.log(result);
          if (result == null) {
            $('#iats-dpm-required').append('<li>Unexpected Error</li>');
          }
          else if ('string' == typeof(result.ACHREFNUM)) {
            $('#bank_name').val(result.BANK_NAME);
            $('#payer_validate_address').val(result.BANK_BRANCH + "\n" + result.BANKADDRESS1 + "\n" + result.BANK_CITY + ", " + result.BANK_STATE + "\n" + result.BANK_POSTCODE);
            $('#payer_validate_reference').val(result.ACHREFNUM);
          }
          else {
            $('#iats-dpm-required').append('<li>' + result.reasonMessage + '</li>');
          }
        },
      })
      .fail(function() {
        $('#iats-dpm-required').append('<li>Unexpected Server Error.</li>');
      });
      // console.log('done');
    }
    if (0 < $('#iats-dpm-required').html().length) {
      $('#iats-dpm-continue .crm-error').show('slow');
      inputButton.val('Retry').prop('disabled',false);
      return false;
    };
    inputButton.val('Contribute').prop('disabled',false);
    return true;
  }); // end of click handler
});
