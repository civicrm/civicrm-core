cj(function ($) {
  'use strict';
  $('#per_membership').click(function() {
    if($(this).is(":checked")) {
      $('#merge_same_address').prop('disabled', true);
      $('#merge_same_household').prop('disabled', true);
      $('#merge_same_address').prop('checked', false);
      $('#merge_same_household').prop('checked', false);
    }
    else {
      $('#merge_same_address').prop('disabled', false);
      $('#merge_same_household').prop('disabled', false);
    }
  });


});
