// http://civicrm.org/licensing
(function($) {
  function civicrm_billingblock_add_cc_logos() {
    $.each(CRM.config.creditCardTypes, function(key, val) {
      var html = '<a href="#" title="' + val + '" class="crm-credit_card_type-logo-' + key + '"><span>' + val + '</span></a>';
      $('.crm-credit_card_type-logos').append(html);

      cj('.crm-credit_card_type-logo-' + key).click(function() {
        cj('#crm-container .credit_card_type-section #credit_card_type').val(val);
        cj('#crm-container .credit_card_type-section a').css('opacity', 0.4);
        cj('#crm-container .credit_card_type-section .crm-credit_card_type-logo-' + key).css('opacity', 1);
        return false;
      });

      // Hide the CC type field (redundant)
      $('#crm-container .credit_card_type-section select#credit_card_type').hide();
      $('#crm-container .credit_card_type-section .label').hide();

      // Select according to the number entered
      cj('#crm-container input#credit_card_number').change(function() {
        var ccnumtype = cj(this).val().substr(0, 4);

        // Semi-hide all images, we will un-hide the right one afterwards
        cj('#crm-container .credit_card_type-section a').css('opacity', 0.4);
        cj('#credit_card_type').val('');

        // https://en.wikipedia.org/wiki/Credit_card_numbers
        if (ccnumtype.substr(0, 1) == '3') {
          cj('#credit_card_type').val('Amex');
          cj('#crm-container .credit_card_type-section .crm-credit_card_type-logo-visa').css('opacity', 1);
        }
        else if (ccnumtype.substr(0, 2) >= '51' && ccnumtype.substr(0, 2) <= '55') {
          cj('#credit_card_type').val('MasterCard');
          cj('#crm-container .credit_card_type-section .crm-credit_card_type-logo-mastercard').css('opacity', 1);
        }
        else if (ccnumtype.substr(0, 1) == '4') {
          cj('#credit_card_type').val('Visa');
          cj('#crm-container .credit_card_type-section .crm-credit_card_type-logo-visa').css('opacity', 1);
        }
      });
    });
  }

  civicrm_billingblock_add_cc_logos();

  $('#crm-container').on('crmFormLoad', '*', function() {
    civicrm_billingblock_add_cc_logos();
  });
})(cj);
