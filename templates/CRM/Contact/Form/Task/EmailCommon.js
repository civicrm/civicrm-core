cj(function ($) {
  'use strict';
   $().crmAccordions();
   $('.form-submit').on("click", function(event){
     $('.form-submit').attr('value','Processing');
     $('.form-submit').attr('disabled','Disabled');
   });
});