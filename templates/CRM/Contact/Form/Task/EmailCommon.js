CRM.$(function($) {
  //do not copy & paste this - find a way to generalise it
  'use strict';
  // NOTE: Target only fullscreen forms (using #crm-container as context) because popups already have this feature
   $('.crm-form-submit', '#crm-container').not('.cancel').on("click", function() {
     $('.crm-form-submit', '#crm-container').not('.cancel').attr({value: ts('Processing')});
     // CRM-13449 : setting a 0 ms timeout is needed
     // for some browsers like chrome. Used for purpose of
     // submit the form and stop accidental multiple clicks
     setTimeout(function(){
       $('.crm-form-submit', '#crm-container').not('.cancel').prop({disabled: true});
     }, 0);
   });
});
