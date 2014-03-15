// http://civicrm.org/licensing
// Adds ajaxy behavior to a simple CiviCRM page
cj(function($) {
  $('#crm-main-content-wrapper')
    // Widgetize the content area
    .crmSnippet()
    // Open action links in a popup
    .off('.crmLivePage')
    .on('click.crmLivePage', 'a.button, a.action-item', CRM.popup)
    .on('crmPopupFormSuccess.crmLivePage', 'a.button, a.action-item', function() {
      // Refresh page when form completes
      $('#crm-main-content-wrapper').crmSnippet('refresh');
    });
});
