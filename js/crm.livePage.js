// http://civicrm.org/licensing
// Adds ajaxy behavior to a simple CiviCRM page
cj(function($) {
  $('#crm-main-content-wrapper')
    // Widgetize the content area
    .crmSnippet()
    // Open action links in a popup
    .off('click.crmLivePage')
    .on('click.crmLivePage', 'a.button, a.action-item', function() {
      var
        dialogSettings = {},
        url = $(this).attr('href');
      // only follow real links not javascript buttons
      if (url === '#' || $(this).attr('onclick') || $(this).hasClass('no-popup')) {
        return;
      }
      // Hack to make delete dialogs smaller
      if (url.indexOf('/delete') > 0 || url.indexOf('action=delete') > 0) {
        dialogSettings.width = 400;
        dialogSettings.height = 300;
      }
      CRM.loadForm(url, {
        openInline: 'a:not("[href=#], .no-popup")',
        dialog: dialogSettings
      }).on('crmFormSuccess', function(e, data) {
        // Refresh page when form completes
        $('#crm-main-content-wrapper').crmSnippet('refresh');
      });
      return false;
    });
});
