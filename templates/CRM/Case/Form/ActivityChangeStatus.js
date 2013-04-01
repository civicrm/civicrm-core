// http://civicrm.org/licensing
cj(function($) {

  $('#crm-container').on('click', 'a.crm-activity-change-status', function() {
    var link = $(this),
      activityId = $(this).attr('activity_id'),
      current_status_id = $(this).attr('current_status'),
      caseId = $(this).attr('case_id'),
      data = 'snippet=1&reset=1',
      o = $('<div class="crm-container crm-activity_change_status"></div>');
    addCiviOverlay(o);

    o.load(CRM.url('civicrm/case/changeactivitystatus'), data, function() {
      removeCiviOverlay(o);
      cj("#activity_change_status").val(current_status_id);
    });

    CRM.confirm(function() {
        // update the status
        var status_id = $("#activity_change_status").val( );
        if (status_id === current_status_id) {
          return false;
        }

        var dataUrl = CRM.url('civicrm/ajax/rest');
        var data = 'json=1&version=3&entity=Activity&action=update&id=' + activityId + '&status_id=' + status_id
          + '&case_id=' + caseId;
        $.ajax({
          type     : 'POST',
          dataType : 'json',
          url      : dataUrl,
          data     : data,
          success  : function(values) {
            if (values.is_error) {
              CRM.alert(values.error_message, ts('Unable to change status'), 'error');
              return false;
            }
            else {
              // reload the table on success
              if (window.buildCaseActivities) {
                // If we are using a datatable
                buildCaseActivities(true);
              }
              else {
                // Legacy refresh for non-datatable screens
                var table = link.closest('table.nestedActivitySelector');
                table.parent().load(CRM.url('civicrm/case/details', table.data('params')));
              }
            }
          },
          error : function(jqXHR) {
            CRM.alert(jqXHR.responseText, jqXHR.statusText, 'error');
            return false;
          }
        });
      }
      ,{
        title: ts('Change Activity Status'),
        message: o
      }
    );
    return false;
  });
});


