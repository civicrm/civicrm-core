CRM.$(function($) {
  //do not copy & paste this - find a way to generalise it
  'use strict';
  // NOTE: Might be safer to say $('[name=_qf_Email_upload]')
   $('.crm-form-submit').not('.cancel').on("click", function() {
     $('.crm-form-submit').not('.cancel').attr({value: ts('Processing')});
     // CRM-13449 : setting a 0 ms timeout is needed 
     // for some browsers like chrome. Used for purpose of
     // submit the form and stop accidental multiple clicks
     setTimeout(function(){
       $('.crm-form-submit').not('.cancel').prop({disabled: true});
     }, 0);
   });
});
