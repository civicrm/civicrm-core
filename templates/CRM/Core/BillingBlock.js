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
      $.each(CRM.config.creditCardTypes, function(card_type_key, val) {
        card_type_css = card_type_key.toLowerCase();
        var html = '<a href="#" title="' + val + '" class="crm-credit_card_type-icon-' + card_type_css + '"><span>' + val + '</span></a>';
        $('.crm-credit_card_type-icons').append(html);

        $('.crm-credit_card_type-icon-' + card_type_css).click(function() {
          $('#credit_card_type').val(card_type_key);
          $('.crm-container .credit_card_type-section a').css('opacity', 0.25);
          $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + card_type_css).css('opacity', 1);
          return false;
        });
      });

      // Hide the CC type field (redundant)
      $('#credit_card_type, .label', '.crm-container .credit_card_type-section').hide();

      // set the card type value as default if any found
      var cardtype = $('#credit_card_type').val();
      if (cardtype) {
        $.each(CRM.config.creditCardTypes, function(card_type_key, value) {
          card_type_css = card_type_key.toLowerCase();
          // highlight the selected card type icon
          if (card_type_key == cardtype) {
            $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + card_type_css).css('opacity', 1);
          }
          else {
            $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + card_type_css).css('opacity', 0.25);
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
    // Based on http://davidwalsh.name/validate-credit-cards
    // See also https://en.wikipedia.org/wiki/Credit_card_numbers
    // @todo these patterns should be part of the credit card option group, instead of hard coded
    var card_types = {
      'MasterCard': '(5[1-5][0-9]{2}|2[3-6][0-9]{2}|22[3-9][0-9]|222[1-9]|27[0-1][0-9]|2720)[0-9]{12}',
      'Visa': '4(?:[0-9]{12}|[0-9]{15})',
      'Amex': '3[47][0-9]{13}',
      'Discover': '6011[0-9]{12}',
    };

    $.each(card_types, function(card_type_key, pattern) {
      card_type_css = card_type_key.toLowerCase();
      if (ccnumber.match('^' + pattern + '$')) {
        $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + card_type_css).css('opacity', 1);
        $('select#credit_card_type').val(card_type_key);
        return false;
      }
    });
  }

  civicrm_billingblock_creditcard_helper();

})(CRM.$);
