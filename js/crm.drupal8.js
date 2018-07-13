// http://civicrm.org/licensing
CRM.$(function($) {
   // d8 Hack to hide title when it should be (CRM-19960)
   $(document).ready(function() {
     var pageTitle = $('.page-title');
     if ('<span id="crm-remove-title" style="display:none">CiviCRM</span>' == pageTitle.text()) {
       pageTitle.hide();
     }

     $('#civicrm-menu').css({position: "fixed", top: "0px", height: "29px"});

     $('#toolbar-bar').hide();
   });
});
