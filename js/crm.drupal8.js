// http://civicrm.org/licensing
CRM.$(function($) {
   // d8 Hack to hide title when it should be (CRM-19960)
   var pageTitle = $('.page-title');
   if ('<span id="crm-remove-title" style="display:none">CiviCRM</span>' == pageTitle.text()) {
     pageTitle.hide();
   }

   $('#toolbar-bar').hide();

   $('body').on('click', '.crm-hidemenu', function() {
     $('#toolbar-bar').slideDown();
   });
   $('#crm-notification-container').on('click', '#crm-restore-menu', function() {
     $('#toolbar-bar').slideUp();
   });
});
