cj(function ($) {
  'use strict';
  $('.form-submit').on("click", function() {
    $('.form-submit').attr({value: ts('Processing'), disabled : 'disabled'});
    $('.crm-form-button-back ').closest('span').hide();
    $('.crm-form-button-cancel').closest('span').hide();
    // CRM-13449 : setting a 0 ms timeout is needed
    // for some browsers like chrome. Used for purpose of
    // submit the form and stop accidental multiple clicks
    setTimeout(function(){
      $('.form-submit').not('.cancel').attr({disabled: 'disabled'});
    }, 0);
  });
});

