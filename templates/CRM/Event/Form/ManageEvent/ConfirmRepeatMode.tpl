{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
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
{if $hasParent || $isRepeatingEntity || $scheduleReminderId}
  <script type="text/template" id="recurring-dialog-tpl">
    <div class="crm-form-block recurring-dialog">
      <h4>{ts}How would you like this change to affect other entities in the repetition set?{/ts}</h4>
      <div class="crm-block">
        <input type="radio" id="recur-only-this-entity" name="recur_mode" value="1">
        <label for="recur-only-this-entity">{ts}Only this entity{/ts}</label>
        <div class="description">{ts}All other entities in the series will remain unchanged.{/ts}</div>

        <input type="radio" id="recur-this-and-all-following-entity" name="recur_mode" value="2">
        <label for="recur-this-and-all-following-entity">{ts}This and Following entities{/ts}</label>
        <div class="description">{ts}Change applies to this and all the following entities.{/ts}</div>

        <input type="radio" id="recur-all-entity" name="recur_mode" value="3">
        <label for="recur-all-entity">{ts}All the entities{/ts}</label>
        <div class="description">{ts}Change applies to all the entities in the series.{/ts}</div>
      </div>
      <div class="status help"><div class="icon ui-icon-lightbulb"></div>{ts}Changes to date or time will NOT be applied to other entities in the series.{/ts}</div>
    </div>
  </script>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      var $form, formClass,
        /** Add your linked entity mapper here **/
        mapper = {
          'CRM_Event_Form_ManageEvent_EventInfo': '',
          'CRM_Event_Form_ManageEvent_Location': '',
          'CRM_Event_Form_ManageEvent_Fee': '',
          'CRM_Event_Form_ManageEvent_Registration': '',
          'CRM_Friend_Form_Event': 'civicrm_tell_friend',
          'CRM_PCP_Form_Event': 'civicrm_pcp_block',
          'CRM_Activity_Form_Activity': ''
        };

      function cascadeChangesDialog() {
        CRM.confirm({
          message: $('#recurring-dialog-tpl').html()
        })
          .on('crmConfirm:yes', updateMode)
          .on('click change', 'input[name=recur_mode]', function() {
            $('button[data-op=yes]').prop('disabled', false);
          })
          .parent().find('button[data-op=yes]').prop('disabled', true)
      }

      $('#crm-main-content-wrapper').on('click', '.crm-form-submit.validate', function(e) {
        $form = $(this).closest('form');
        var className = ($form.attr('class') || '').match(/CRM_\S*/);
        formClass = className && className[0];
        if (formClass && mapper.hasOwnProperty(formClass) &&
            // For activities, only show this if the changes were not made to the recurring settings
          (formClass !== 'CRM_Activity_Form_Activity' || !CRM.utils.initialValueChanged('.crm-core-form-recurringentity-block'))
        ) {
          cascadeChangesDialog();
          e.preventDefault();
        }
      });

      function updateMode() {
        var mode = $('input[name=recur_mode]:checked', this).val(),
          entityID = parseInt('{/literal}{$entityID}{literal}'),
          entityTable = '{/literal}{$entityTable}{literal}';
        if (entityID != "" && mode && mapper.hasOwnProperty(formClass) && entityTable !="") {
          $.getJSON(CRM.url("civicrm/ajax/recurringentity/update-mode",
              {mode: mode, entityId: entityID, entityTable: entityTable, linkedEntityTable: mapper[formClass]})
          ).done(function (result) {
              if (result.status != "" && result.status == 'Done') {
                $form.submit();
              } else if (result.status != "" && result.status == 'Error') {
                if (confirm(ts("Mode could not be updated, save only this event?"))) {
                  $form.submit();
                }
              }
            });
        }
      }
    });
  </script>
{/literal}
{/if}
