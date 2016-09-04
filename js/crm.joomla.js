// http://civicrm.org/licensing
CRM.$(function($) {
  $(document)
    .on('dialogopen', function(e) {
      // J3 - Make footer admin bar hide behind popup windows (CRM-15723)
      $('#status').css('z-index', '100');
    })
    .on('dialogclose', function(e) {
      if ($('.ui-dialog-content:visible').not(e.target).length < 1) {
        // J3 - restore footer admin bar position (CRM-15723)
        $('#status').css('z-index', '');
      }
    });
});
