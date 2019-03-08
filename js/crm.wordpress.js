// http://civicrm.org/licensing
CRM.$(function($) {
  $(document)
    .on('dialogopen', function(e) {
      // Make admin bar hide behind popup windows
      $('#adminmenuwrap').css('z-index', '100');
    })
    .on('dialogclose', function(e) {
      if ($('.ui-dialog-content:visible').not(e.target).length < 1) {
        // Restore admin bar position
        $('#adminmenuwrap').css('z-index', '');
      }
    })
    .on('crmWysiwygCreate', function(e, type, editor) {
      if (type === 'ckeditor') {
        editor.on('maximize', function(e) {
          $('#wpadminbar').toggle(e.data === 2);
        });
      }
    });
  // Prevent screen reader shortcuts from changing the document hash and breaking angular routes
  $('a.screen-reader-shortcut').click(function() {
    var href = $(this).attr('href');
    // Show toolbar if hidden
    if (href === '#wp-toolbar' && CRM.menubar.position === 'over-cms-menu') {
      CRM.menubar.togglePosition(false);
    }
    $(href).focus();
    return false;
  });
  $('<a href="#crm-qsearch-input" class="screen-reader-shortcut">' + ts("Open CiviCRM Menu") + '</a>')
    .prependTo('#adminmenumain')
    .click(function() {
      CRM.menubar.open('Home');
      return false;
    });
});
