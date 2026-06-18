// https://civicrm.org/licensing
CRM.$(function($) {
  $(document)
    .on('crmWysiwygCreate', function(e, type, editor) {
      if (type === 'ckeditor') {
        editor.on('maximize', function(e) {
          $('#wpadminbar').toggle(e.data === 2);
        });
      }
    });
  // Prevent screen reader shortcuts from changing the document hash and breaking angular routes.
  $('a.screen-reader-shortcut').click(function() {
    var target = $(this).attr('href');
    // Show toolbar if hidden.
    if (target === '#wp-toolbar' && CRM.menubar.position === 'over-cms-menu') {
      CRM.menubar.togglePosition(false);
    }
    // The targets are "div" elements so need special handling.
    $(target).css('outline', 'none !important').attr("tabindex", -1).trigger('focus');
    // Prevent unwanted jump.
    $('html').scrollTop(0);
    return false;
  });
  $('<a href="#crm-qsearch-input" class="screen-reader-shortcut">' + ts("Open CiviCRM Menu") + '</a>')
    .prependTo('#adminmenumain')
    .click(function() {
      CRM.menubar.open('Home');
      return false;
    });
});
