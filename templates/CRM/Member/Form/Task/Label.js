cj(function ($) {
  'use strict';
  $('#per_membership').click(function() {
    if($(this).is(":checked")) {
      $('#merge_same_address').attr('disabled', true);
      $('#merge_same_household').attr('disabled', true);
      $('#merge_same_address').prop('checked', false);
      $('#merge_same_household').prop('checked', false);
    }
    else {
      $('#merge_same_address').attr('disabled', false);
      $('#merge_same_household').attr('disabled', false);
    }
  });


});
