// http://civicrm.org/licensing
// Adds ajaxy behavior to a simple CiviCRM page
cj(function($) {
  $('#crm-main-content-wrapper')
    // Widgetize the content area
    .crmSnippet()
    // Open action links in a popup
    .on('click', 'a.button, a.action-item:not(".enable-action, .disable-action")', function() {
      CRM.loadForm($(this).attr('href'), {
        openInline: 'a'
      }).on('crmFormSuccess', function(e, data) {
        // Refresh page when form completes
        $('#crm-main-content-wrapper').crmSnippet('refresh');
      });
      return false;
    });
});
