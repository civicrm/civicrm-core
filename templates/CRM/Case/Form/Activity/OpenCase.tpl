{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $context ne 'caseActivity'}
  <tr class="crm-case-opencase-form-block-case_type_id">
    <td class="label">{$form.case_type_id.label}{help id="case_type_id" file="CRM/Case/Form/Case.hlp" activityTypeFile=$activityTypeFile}</td>
    <td>{$form.case_type_id.html}</td>
  </tr>
  <tr class="crm-case-opencase-form-block-status_id">
    <td class="label">{$form.status_id.label}</td>
    <td>{$form.status_id.html}</td>
  </tr>
  <tr class="crm-case-opencase-form-block-start_date">
    <td class="label">{$form.start_date.label}</td>
    <td>{$form.start_date.html}</td>
  </tr>

{* Add fields for attachments *}
  {if $action eq 4 AND $currentAttachmentURL}
    {include file="CRM/Form/attachment.tpl"}{* For view action the include provides the row and cells. *}
  {elseif $action eq 1 OR $action eq 2}
    <tr class="crm-activity-form-block-attachment">
      <td colspan="2">
      {include file="CRM/Form/attachment.tpl"}
      </td>
    </tr>
  {/if}
  {crmAPI var='caseTypes' entity='CaseType' action='get' option_limit=0 sequential=0}
  {crmAPI var='caseStatusLabels' entity='Case' action='getoptions' option_limit=0 field="case_status_id" context='create'}
  {crmAPI var='caseStatusNames' entity='Case' action='getoptions' option_limit=0 field="case_status_id" context='validate' sequential=0}
  {literal}
  <script type="text/javascript">
    CRM.$(function($) {
      var $form = $("form.{/literal}{$form.formClass}{literal}");
      var caseTypes = {/literal}{$caseTypes.values|@json_encode}{literal};
      var caseStatusLabels = {/literal}{$caseStatusLabels.values|@json_encode}{literal};
      var caseStatusNames = {/literal}{$caseStatusNames.values|@json_encode}{literal};
      if ($('#case_type_id, #status_id', $form).length === 2) {
        updateCaseStatusOptions();
        $('#case_type_id', $form).change(updateCaseStatusOptions);
        function updateCaseStatusOptions() {
          if ($('#case_type_id', $form).val()) {
            var definition = caseTypes[$('#case_type_id', $form).val()].definition;
            var newOptions = CRM._.filter(caseStatusLabels, function(opt) {
              return !definition.statuses || !definition.statuses.length || definition.statuses.indexOf(caseStatusNames[opt.key]) > -1;
            });
            CRM.utils.setOptions($('#status_id', $form), newOptions);
          }
        }
      }
    });
  </script>
  {/literal}
{/if}
