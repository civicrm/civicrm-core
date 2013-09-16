cj(function ($) {
  //do not copy & paste this - find a way to generalise it
  'use strict';
   $().crmAccordions();
   $('.form-submit').on("click", function() {
     $('.form-submit').attr({value: ts('Processing'), disabled: 'disabled'});
   });
});
