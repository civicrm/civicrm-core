CRM.$(function($) {
  'use strict';
  $('#per_membership').click(function() {
    if($(this).is(":checked")) {
      $('#merge_same_address, #merge_same_household').prop({disabled: true, checked: false});
    }
    else {
      $('#merge_same_address, #merge_same_household').prop('disabled', false);
    }
  });

});
