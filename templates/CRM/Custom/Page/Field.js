// http://civicrm.org/licensing
cj(function($) {
  $('#crm-main-content-wrapper')
    // Widgetize the content area
    .crmSnippet({block: false})
    // Open action links in a popup
    .on('click', 'a.action-item:not(".enable-action, .disable-action")', function() {
      CRM.loadForm(this.href).on('crmFormSuccess', function(e, data) {
        // Refresh page when form completes
        $('#crm-main-content-wrapper').crmSnippet('refresh');
      });
      return false;
    });
});
