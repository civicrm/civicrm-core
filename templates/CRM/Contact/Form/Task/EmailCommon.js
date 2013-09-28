cj(function ($) {
  //do not copy & paste this - find a way to generalise it
  'use strict';
   $().crmAccordions();
<<<<<<< HEAD
  // NOTE: Might be safer to say $('[name=_qf_Email_upload]')
   $('.form-submit').not('.cancel').on("click", function() {
     $('.form-submit').not('.cancel').attr({value: ts('Processing')});
     // CRM-13449 : setting a 0 ms timeout is needed 
     // for some browsers like chrome. Used for purpose of
     // submit the form and stop accidental multiple clicks
     setTimeout(function(){
       $('.form-submit').not('.cancel').attr({disabled: 'disabled'});
     }, 0);
   });
=======
>>>>>>> CRM-13379 js, contribution form - generalise  button double submit protection and add to contribution.main to prevent double submits where people have no confirm page
});
