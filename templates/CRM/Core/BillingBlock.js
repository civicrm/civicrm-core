// http://civicrm.org/licensing
(function($) {

  /**
   * Adds the icons of enabled credit cards
   * Handles clicking on a icon.
   * Changes the icon depending on the credit card number.
   * Removes spaces and dashes from credit card numbers.
   */
  function civicrm_billingblock_creditcard_helper() {
    $.each(CRM.config.creditCardTypes, function(key, val) {
      var html = '<a href="#" title="' + val + '" class="crm-credit_card_type-icon-' + key + '"><span>' + val + '</span></a>';
      $('.crm-credit_card_type-icons').append(html);

      $('.crm-credit_card_type-icon-' + key).click(function() {
        $('#credit_card_type').val(val);
        $('.crm-container .credit_card_type-section a').css('opacity', 0.25);
        $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + key).css('opacity', 1);
        return false;
      });
    });

    // Hide the CC type field (redundant)
    $('#credit_card_type, .label', '.crm-container .credit_card_type-section').hide();

    // Select according to the number entered
    $('.crm-container input#credit_card_number').change(function() {
      var ccnumber = cj(this).val();

      // Remove spaces and dashes
      ccnumber = ccnumber.replace(/[- ]/g, '');
      cj(this).val(ccnumber);

      // Semi-hide all images, we will un-hide the right one afterwards
      $('.crm-container .credit_card_type-section a').css('opacity', 0.25);
      $('#credit_card_type').val('');

      civicrm_billingblock_set_card_type(ccnumber);
    });
  }

  function civicrm_billingblock_set_card_type(ccnumber) {
    // Based on http://davidwalsh.name/validate-credit-cards
    // See also https://en.wikipedia.org/wiki/Credit_card_numbers
    var card_types = {
      'mastercard': '5[1-5][0-9]{14}',
      'visa': '4(?:[0-9]{12}|[0-9]{15})',
      'amex': '3[47][0-9]{13}',
      'dinersclub': '3(?:0[0-5][0-9]{11}|[68][0-9]{12})',
      'carteblanche': '3(?:0[0-5][0-9]{11}|[68][0-9]{12})',
      'discover': '6011[0-9]{12}',
      'jcb': '(?:3[0-9]{15}|(2131|1800)[0-9]{11})',
      'unionpay': '62(?:[0-9]{14}|[0-9]{17})'
    }

    var card_values = {
      'mastercard': 'MasterCard',
      'visa': 'Visa',
      'amex': 'Amex',
      'dinersclub': 'Diners Club',
      'carteblanche': 'Carte Blanche',
      'discover': 'Discover',
      'jcb': 'JCB',
      'unionpay': 'UnionPay'
    }

    $.each(card_types, function(key, pattern) {
      if (ccnumber.match('^' + pattern + '$')) {
        var value = card_values[key];
        $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + key).css('opacity', 1);
        $('select#credit_card_type').val(value);
        return false;
      }
    });
  }

  civicrm_billingblock_creditcard_helper();

  $(function() {
    $('#billing-payment-block').on('crmFormLoad', civicrm_billingblock_creditcard_helper);
  });
})(CRM.$);
