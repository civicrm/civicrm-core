// http://civicrm.org/licensing
// Adds ajaxy behavior to a simple CiviCRM page
cj(function($) {
  $('#crm-main-content-wrapper')
    // Widgetize the content area
    .crmSnippet()
    // Open action links in a popup
    .off('click.crmLivePage')
    .on('click.crmLivePage', 'a.button, a.action-item', function() {
      var url = $(this).attr('href');
      // only follow real links not javascript buttons
      if (url === '#' || $(this).attr('onclick') || $(this).hasClass('no-popup')) {
        return;
      }
      CRM.loadForm(url, {
        openInline: 'a:not("[href=#], .no-popup")'
      }).on('crmFormSuccess', function(e, data) {
        // Refresh page when form completes
        $('#crm-main-content-wrapper').crmSnippet('refresh');
      });
      return false;
    });
});
