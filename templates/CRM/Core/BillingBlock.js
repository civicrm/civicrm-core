// http://civicrm.org/licensing
(function($, _) {

  $(function() {
    $.each(CRM.config.creditCardTypes, function(key, val) {
      var html = '<a href="#" title="' + _.escape(val.label) + '" class="crm-credit_card_type-icon-' + val.css_key + '"><span>' + _.escape(val.label) + '</span></a>';
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
      var ccnumber = $(this).val();

      // Remove spaces and dashes
      ccnumber = ccnumber.replace(/[- ]/g, '');
      $(this).val(ccnumber);

      // Semi-hide all images, we will un-hide the right one afterwards
      $('.crm-container .credit_card_type-section a').css('opacity', 0.25);
      $('#credit_card_type').val('');

      setCardtype(ccnumber);
    });

    function setCardtype(ccnumber) {
      $.each(CRM.config.creditCardTypes, function(key, spec) {
        if (ccnumber.match('^' + spec.pattern + '$')) {
          $('.crm-container .credit_card_type-section .crm-credit_card_type-icon-' + spec.css_key).css('opacity', 1);
          $('select#credit_card_type').val(key);
          return false;
        }
      });
    }

  });

})(CRM.$, CRM._);
