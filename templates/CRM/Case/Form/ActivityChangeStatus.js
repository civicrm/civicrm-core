// http://civicrm.org/licensing
cj(function($) {
  $('.crm-container').on('click', 'a.crm-activity-change-status', function() {
    changeActivityStatus(
      $(this).attr('activity_id'),
      $(this).attr('current_status'),
      $(this).attr('case_id')
    );
    return false;
  });

  function changeActivityStatus(activityId, current_status_id, caseId) {
    var o = $('<div class="crm-container crm-activity_change_status"></div>');
    addCiviOverlay(o);

    var data = 'snippet=1&reset=1';
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
              // just reload the page on success
              window.location.reload();
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
  }
});


