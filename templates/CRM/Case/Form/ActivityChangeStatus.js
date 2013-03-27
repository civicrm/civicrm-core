// http://civicrm.org/licensing
function changeActivityStatus(activityId, contactId, current_status_id, caseId) {
  var data = 'snippet=1&reset=1';
  cj('<div>')
    .load(CRM.url('civicrm/case/changeactivitystatus'), data, function() {
      cj("#activity_change_status").val(current_status_id);
    })
    .dialog({
      modal: true,
      title: ts('Change Activity Status'),
      buttons: {
        "Ok" : function() {
          // update the status
          var status_id = cj("#activity_change_status").val( );

          if (status_id === current_status_id) {
            return false;
          }

          var dataUrl = CRM.url('civicrm/ajax/rest');
          var data = 'json=1&version=3&entity=Activity&action=update&id=' + activityId + '&status_id=' + status_id
            + '&case_id=' + caseId;
          cj.ajax({
            type     : "POST",
            dataType : "json",
            url      : dataUrl,
            data     : data,
            success  : function( values ) {
              if ( values.is_error ) {
                // seems to be some discrepancy as to which spelling it should be
                var err_msg = values.error_msg ? values.error_msg : values.error_message;
                CRM.alert(err_msg, ts('Unable to change status'), 'error');
                return false;
              }
              else {
                // just reload the page on success
                window.location.reload();
              }
            },
            error    : function( jqXHR, textStatus ) {
              CRM.alert(jqXHR.responseText, jqXHR.statusText, 'error');
              return false;
            }
          });

          cj(this).dialog('close').remove();
        },
        "Cancel": function() {
          cj(this).dialog('close').remove();
        }
      },
      beforeClose: function() {
        cj(this).dialog("destroy");
      }
    }
  );
}


