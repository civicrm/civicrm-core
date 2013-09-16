cj(function ($) {
  //do not copy & paste this - find a way to generalise it
  'use strict';
   $().crmAccordions();
  // NOTE: Might be safer to say $('[name=_qf_Email_upload]')
   $('.form-submit').not('.cancel').on("click", function() {
     $('.form-submit').not('.cancel').attr({value: ts('Processing'), disabled: 'disabled'});
   });
});
