{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if !empty($hasParent) || !empty($isRepeatingEntity) || !empty($scheduleReminderId)}
  {capture assign='entity_type'}{$recurringEntityType|lower}{/capture}
  <script type="text/template" id="recurring-dialog-tpl">
    <div class="recurring-dialog">
      <h4>{ts}How should this change affect others in the series?{/ts}</h4>
      <div>
        <input type="radio" id="recur-only-this-entity" name="recur_mode" value="1">
        <label for="recur-only-this-entity">{ts 1=$entity_type}Only this %1{/ts}</label>
        <div class="description">{ts}All others in the series will remain unchanged.{/ts}</div>

        <input type="radio" id="recur-this-and-all-following-entity" name="recur_mode" value="2">
        <label for="recur-this-and-all-following-entity">{ts 1=$entity_type}This %1 onwards{/ts}</label>
        <div class="description">{ts 1=$entity_type}Change applies to this %1 and all that come after it.{/ts}</div>

        <input type="radio" id="recur-all-entity" name="recur_mode" value="3">
        <label for="recur-all-entity">{ts 1=$entity_type}Every %1{/ts}</label>
        <div class="description">{ts 1=$entity_type}Change applies to every %1 in the series.{/ts}</div>
      </div>
      <div class="status help"><i class="crm-i fa-info-circle" aria-hidden="true"></i> {ts}Changes to date or time will <em>not</em> be applied to others in the series.{/ts}</div>
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
          title: "{/literal}{ts escape='js' 1=$entity_type}Update repeating %1{/ts}{literal}",
          message: $('#recurring-dialog-tpl').html()
        })
          .on('crmConfirm:yes', updateMode)
          .on('click change', 'input[name=recur_mode]', function() {
            $('button[data-op=yes]').prop('disabled', false);
          })
          .parent().find('button[data-op=yes]').prop('disabled', true)
      }

      // Intercept form submissions and check if they will impact the recurring entity
      // This ought to attach the handler to the the dialog if we're in a popup, or the page wrapper if we're not
      $('#recurring-dialog-tpl').closest('.crm-container').on('click', '.crm-form-submit.validate', function(e) {
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
          entityTable = '{/literal}{$entityTable}{literal}',
	  priceSet = $('#price_set_id').val();
        if (entityID != "" && mode && mapper.hasOwnProperty(formClass) && entityTable !="") {
          $.getJSON(CRM.url("civicrm/ajax/recurringentity/update-mode",
              {mode: mode, entityId: entityID, entityTable: entityTable, linkedEntityTable: mapper[formClass], priceSet: priceSet})
          ).done(function (result) {
              if (result.status != "" && result.status == 'Done') {
                $form.submit();
              } else if (result.status != "" && result.status == 'Error') {
                if (confirm("{/literal}{ts escape='js' 1=$entity_type}Mode could not be updated, save only this %1?{/ts}{literal}")) {
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
