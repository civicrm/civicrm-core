{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{* CiviCase - change activity status inline *}
<div id="changeStatusDialog">
<div class="crm-block crm-form-block crm-case-activitychangestatus-form-block">
<table class="form-layout">
     <tr class="crm-case-activitychangestatus-form-block-status">
      <td class="label">{$form.activity_change_status.label}</td>
       <td>{$form.activity_change_status.html}</td>
     </tr>
</table>
</div>
</div>

{literal}
<script type="text/javascript">
cj( "#changeStatusDialog" ).hide( );
function changeActivityStatus( activityID, contactId, current_status_id ) {

    cj("#changeStatusDialog").show();
    cj("#changeStatusDialog").dialog({
        title       : "Change Activity Status",
        modal       : true,
        bgiframe    : true,
        width       : 400,
        height      : 170,
        close       : function( event, ui ) { },
        overlay     : { opacity: 0.5, background: "black" },
        beforeclose : function( event, ui ) {
            cj(this).dialog("destroy");
        },
        open        : function() {
            cj("#activity_change_status").val( current_status_id );
        },

        buttons : {
      "Ok": function() {
                var status_id = cj("#activity_change_status").val( );

                cj(this).dialog("destroy");

                if ( status_id == current_status_id  ) {
                    return false;
                }

                var dataUrl = {/literal}"{crmURL p='civicrm/ajax/rest' h=0 }"{literal};
                var data = 'json=1&version=3&entity=Activity&action=update&id=' + activityID + '&status_id=' + status_id + '&case_id=' + {/literal}{$caseId}{literal};
                cj.ajax({   type     : "POST",
                            dataType : "json",
                            url      : dataUrl,
                            data     : data,
                            success  : function( values ) {
                                if ( values.is_error ) {
                                    // seems to be some discrepancy as to which spelling it should be
                                    var err_msg = values.error_msg ? values.error_msg : values.error_message;
                                    CRM.alert(err_msg, '{/literal}{ts escape="js"}Unable to change status{/ts}{literal}', 'error');
                                    return false;
                                } else {
                                    // Hmm, actually several links inside the row have to change to use the new activity id
                                    // and also the row class might change with the new status. So either we duplicate code here,
                                    // do a reload which defeats the purpose of ajax, or rewrite the way this table works.
                                    //cj( "a.crm-activity-status-" + activityID ).html(
                                    //    cj("#activity_change_status option[value='" + status_id + "']").text()
                                    //);
                                    window.location.reload();
                                }
                            },
                            error    : function( jqXHR, textStatus, errorThrown ) {
                                CRM.alert(jqXHR.responseText, jqXHR.statusText, 'error');
                                return false;
                            }
                });
            },

            "Cancel": function() {
                cj(this).dialog("close");
                cj(this).dialog("destroy");
            }
        }
    });
}
</script>
{/literal}
