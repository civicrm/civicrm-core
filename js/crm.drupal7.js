// http://civicrm.org/licensing
(function($) {
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
    .on('crmLoad', '#civicrm-menu', function(e) {
      if ($('#toolbar a.toggle').length) {
        $('#civicrm-menu').css({width: 'calc(100% - 40px)'});
      }
    });
})(CRM.$);
