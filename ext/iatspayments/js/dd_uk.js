/* custom js for uk dd */
/*jslint indent: 2 */
/*global CRM, ts */

function iatsUKDDRefresh() {
  cj(function ($) {
    'use strict';
    if (0 < $('#iats-direct-debit-gbp-payer-validate').length) {
      /* move my custom fields around and make it a multistep form experience via javascript */
      /* move my custom fields up where they belong */
      var pgDeclaration = $('#iats-direct-debit-gbp-declaration');
      var pgNonDeclaration = $('.crm-contribution-main-form-block');
      var pgPayerValidate = $('#billing-payment-block');
      // var pgPayerValidateHide = $('#billing-payment-block');
      /* i don't want to show my default civicrm payment notice */
      $('#payment_notice').hide();
      /* move some fields around to better flow like the iATS DD samples */
      $('input[name|="email"]').parents('.crm-section').prependTo('.billing_name_address-section');
      $('.direct_debit_info-section').before($('#iats-direct-debit-extra'));
      $('.is_recur-section').after($('#iats-direct-debit-start-date'));
      $('.crm-contribution-main-form-block').before($('#iats-direct-debit-gbp-declaration'));
      $('#payer_validate_amend').hide();
      /* page 1: Declaration */
      if ($('#payer_validate_declaration').is(':checked')) {
        pgDeclaration.hide();
      }
      else {
        pgNonDeclaration.hide();
      }
      $('#payer_validate_declaration').change(function() {
        if (this.checked) {
          pgDeclaration.hide('slow');
          pgNonDeclaration.show('slow');
        }
        else {
          pgDeclaration.hide('slow');
          pgNonDeclaration.show('slow');
        }
      });
      /* page 2: Normal CiviCRM form with extra fields */
      // hide fields that are used for error display before validation
      $('#iats-direct-debit-gbp-continue .crm-error').hide();
      // hide the fields that will get validation field lookup values
      $('#iats-direct-debit-gbp-payer-validate').hide();
      $('.bank_name-section').hide(); // I don't ask this!
      /* initiate a payer validation: check for required fields, then do a background POST to retrieve bank info */
      $('#crm-submit-buttons .crm-button input').click(function( defaultSubmitEvent ) {
        var inputButton = $(this);
        inputButton.val('Processing ...').prop('disabled',true);
        // reset the list of errors
        $('#payer-validate-required').html('');
        // the billing address group is all required (except middle name) but doesn't have the class marked
        $('#Main .billing_name_address-group input:visible, #Main input.required:visible').each(function() {
          // console.log(this.value.length);
          if (0 == this.value.length && this.id != 'billing_middle_name') {
            if ('installments' == this.id) { // todo: check out other exceptions
              var myLabel = 'Installments';
            }
            else {
              var myLabel = cj.trim($(this).parent('.content').prev('.label').find('label').text().replace('*',''));
            }
            if (myLabel.length > 0) { // skip any weird hidden fields (e.g. select2-offscreen class)
              $('#payer-validate-required').append('<li>' + myLabel + ' is a required field.</li>');
            }
          }
        })
        // if all pre-validation requirements are met, I can do the sycronous POST to try to get my banking information
        if (0 == $('#payer-validate-required').html().length) {
          var validatePayer = {};
          var startDateStr = $('#payer_validate_start_date').val();
          var startDate = new Date(startDateStr);
          validatePayer.beginDate = cj.datepicker.formatDate('yy-mm-dd',startDate);
          var endDate = startDate;
          var frequencyInterval = $('input[name=frequency_interval]').val();
          var frequencyUnit = $('[name="frequency_unit"]').val();
          var installments = $('input[name="installments"]').val();
          switch(frequencyUnit) {
            case 'year':
              var myYear = endDate.getFullYear() + (frequencyInterval * installments);
              endDate.setFullYear(myYear);
              break;
            case 'month':
              var myMonth = endDate.getMonth() + (frequencyInterval * installments);
              endDate.setMonth(myMonth);
              break;
            case 'week':
              var myDay = endDate.getDate() + (frequencyInterval * installments * 7);
              endDate.setDate(myDay);
              break;
            case 'day':
              var myDay = endDate.getDate() + (frequencyInterval * installments * 1);
              endDate.setDate(myDay);
              break;
          }
          validatePayer.endDate = endDate.toISOString();
          validatePayer.firstName = $('#billing_first_name').val();
          validatePayer.lastName = $('#billing_last_name').val();
          validatePayer.address = $('input[name|="billing_street_address"]').val();
          validatePayer.city = $('input[name|="billing_city"]').val();
          validatePayer.zipCode = $('input[name|="billing_postal_code"]').val();
          validatePayer.country = $('input[name|="billing_country_id"]').find('selected').text();
          validatePayer.accountCustomerName = $('#account_holder').val();
          validatePayer.accountNum = $('#bank_identification_number').val() + $('#bank_account_number').val();
          validatePayer.email = $('input[name|="email"]').val();
          validatePayer.ACHEFTReferenceNum = '';
          validatePayer.companyName = '';
          validatePayer.type = 'customer';
          validatePayer.method = 'direct_debit_acheft_payer_validate';
          validatePayer.payment_processor_id = $('input[name="payment_processor"]').val();
          var payerValidateUrl = $('input[name="payer_validate_url"]').val();
          // console.log(payerValidateUrl);
          // console.log(validatePayer);
          /* this ajax call has to be sychronous! */
          cj.ajax({
            type: 'POST',
            url: payerValidateUrl,
            data: validatePayer,
            dataType: 'json',
            async: false,
            success: function(result) {
              // console.log(result);
              if (result == null) {
                $('#payer-validate-required').append('<li>Unexpected Error</li>');
              }
              else if ('string' == typeof(result.ACHREFNUM)) {
                $('#bank_name').val(result.BANK_NAME);
                $('#payer_validate_address').val(result.BANK_BRANCH + "\n" + result.BANKADDRESS1 + "\n" + result.BANK_CITY + ", " + result.BANK_STATE + "\n" + result.BANK_POSTCODE);
                $('#payer_validate_reference').val(result.ACHREFNUM);
              }
              else {
                $('#payer-validate-required').append('<li>' + result.reasonMessage + '</li>');
              }
            },
          })
          .fail(function() {
            $('#payer-validate-required').append('<li>Unexpected Server Error.</li>');
          });
          // console.log('done');
        }
        if (0 < $('#payer-validate-required').html().length) {
          $('#iats-direct-debit-gbp-continue .crm-error').show('slow');
          inputButton.val('Retry').prop('disabled',false);
          return false;
        };
        inputButton.val('Contribute').prop('disabled',false);
        return true;
      }); // end of click handler
    }
  });
}
