cj(function ($) {
  'use strict';
  $('form#Main').submit(function() {
    $('.form-submit').attr({
      value: ts('Processing'),
      disabled : 'disabled'
    });
    $('.crm-form-button-back').closest('span').hide();
    $('.crm-form-button-cancel').closest('span').hide();
  });
});
