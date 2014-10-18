{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      // Elements are sometimes in a jQuery dialog box which is outside crm-container,
      // So gotta attach this handler to the whole body - sorry.
      $('body').off('click.changeActivityStatus');
      $('body').on('click.changeActivityStatus', 'a.crm-activity-change-status', function() {
        var link = $(this),
          activityId = $(this).attr('activity_id'),
          current_status_id = $(this).attr('current_status'),
          caseId = $(this).attr('case_id'),
          data = 'snippet=1&reset=1',
          $el = $('<div class="crm-activity_change_status"></div>');
        $el.block();

        $el.load(CRM.url('civicrm/case/changeactivitystatus'), data, function() {
          $el.unblock().trigger('crmLoad');
          $("#activity_change_status").val(current_status_id);
        });

        CRM.confirm({
          title: {/literal}'{ts escape='js'}Change Activity Status{/ts}'{literal},
          message: $el
        })
          .on('crmConfirm:yes', function() {
            // update the status
            var status_id = $("#activity_change_status").val();
            if (status_id === current_status_id) {
              return false;
            }

            var dataUrl = CRM.url('civicrm/ajax/rest');
            var data = 'json=1&version=3&entity=Activity&action=update&id=' + activityId + '&status_id=' + status_id
              + '&case_id=' + caseId;
            var request = $.ajax({
              type     : 'POST',
              dataType : 'json',
              url      : dataUrl,
              data     : data,
              success  : function(values) {
                if (values.is_error) {
                  CRM.alert(values.error_message, {/literal}'{ts escape='js'}Unable to change status{/ts}'{literal}, 'error');
                  return false;
                }
                else {
                  CRM.refreshParent(link);
                }
              },
              error : function(jqXHR) {
                CRM.alert(jqXHR.responseText, jqXHR.statusText, 'error');
                return false;
              }
            });
            CRM.status({}, request);
          });
        return false;
      });
    });
  </script>
{/literal}
