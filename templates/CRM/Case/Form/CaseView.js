// https://civicrm.org/licensing
cj(function($) {

  function refresh() {
    $('#crm-main-content-wrapper').crmSnippet('refresh');
  }

  function open(url) {
    if (CRM.config.ajaxPopupsEnabled) {
      CRM.loadForm(url).on('crmFormSuccess', refresh);
    }
    else {
      window.location = url;
    }
  }

  $('#crm-container')
    .on('change', 'select[name=add_activity_type_id]', function() {
      open($(this).val());
      $(this).select2('val', '');
    });
});
