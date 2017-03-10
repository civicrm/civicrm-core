// http://civicrm.org/licensing
CRM.$(function($) {
  $(document)
    .on('dialogopen', function(e) {
      // D7 hack to get the toolbar out of the way (CRM-15341)
      $('#toolbar').css('z-index', '100');
    })
    .on('dialogclose', function(e) {
      if ($('.ui-dialog-content:visible').not(e.target).length < 1) {
        // D7 hack, restore toolbar position (CRM-15341)
        $('#toolbar').css('z-index', '');
      }
    })
   // d8 Hack to hide title when it should be (CRM-19960)
   .ready(function() {
     var pageTitle = $('.page-title');
     var title = $('.page-title').text();
     if ('<span id="crm-remove-title" style="display:none">CiviCRM</span>' == title) {
       pageTitle.hide();
     }
   });
});
