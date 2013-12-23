// http://civicrm.org/licensing
// Adds ajaxy behavior to a simple CiviCRM page
cj(function($) {
  $('#crm-main-content-wrapper')
    // Widgetize the content area
    .crmSnippet()
    // Open action links in a popup
    .on('click', 'a.button, a.action-item', function() {
      // only follow real links not javascript buttons
      if ($(this).attr('href') === '#' || $(this).attr('onclick')) {
        return;
      }
      CRM.loadForm($(this).attr('href'), {
        openInline: 'a:not([href="#"])'
      }).on('crmFormSuccess', function(e, data) {
        // Refresh page when form completes
        $('#crm-main-content-wrapper').crmSnippet('refresh');
      });
      return false;
    });
});
