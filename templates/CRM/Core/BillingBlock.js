// http://civicrm.org/licensing
(function($) {

  /**
   * Adds the icons of enabled credit cards
   * Handles clicking on a icon.
   * Changes the icon depending on the credit card number.
   * Removes spaces and dashes from credit card numbers.
   */
  function civicrm_billingblock_creditcard_helper() {
    $(function() {
      $.each(CRM.config.creditCardTypes, function(key, val) {
        var html = '<a href="#" data-card_type=" + key + " title="' + val + '" class="crm-credit_card_type-icon-' + val.css_key + '"><span>' + val.label + '</span></a>';
        $('.crm-credit_card_type-icons').append(html);

        $('.crm-credit_card_type-icon-' + val.css_key).click(function() {
          $('#credit_card_type').val(key);
          $('.crm-container .credit_card_type-section a').css('opacity', 0.25);
          $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + val.css_key).css('opacity', 1);
          return false;
        });
      });

      // Hide the CC type field (redundant)
      $('#credit_card_type, .label', '.crm-container .credit_card_type-section').hide();

      // set the card type value as default if any found
      var cardtype = $('#credit_card_type').val();
      if (cardtype) {
        $.each(CRM.config.creditCardTypes, function(key, val) {
          // highlight the selected card type icon
          if (key === cardtype) {
            $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + val.css_key).css('opacity', 1);
          }
          else {
            $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + val.css_key).css('opacity', 0.25);
          }
        });
      }

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
    });
  }

  function civicrm_billingblock_set_card_type(ccnumber) {
    var card_values = CRM.config.creditCardTypes;
    $.each(card_values, function(key, spec) {
      if (ccnumber.match('^' + spec.pattern + '$')) {
        $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + spec.css_key).css('opacity', 1);
        $('select#credit_card_type').val(key);
        return false;
      }
    });
  }

  civicrm_billingblock_creditcard_helper();

})(CRM.$);
