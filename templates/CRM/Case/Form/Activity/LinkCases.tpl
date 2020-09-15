{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Template for to create a link between two cases. *}
   <div class="crm-block crm-form-block crm-case-linkcases-form-block">
    <tr class="crm-case-linkcases-form-block-link_to_case_id">
      <td class="label">{$form.link_to_case_id.label}</td>
      <td>{$form.link_to_case_id.html}</td>
    </tr>

{literal}
<script type="text/javascript">
  CRM.$(function($) {
    var $form = $("form.{/literal}{$form.formClass}{literal}");
    $('input[name=link_to_case_id]', $form).change(function() {
      if ($(this).val()) {
        var info = $(this).select2('data').extra;
        {/literal}{* Mix in variables and placeholders for clientside substitution *}
        var subject = "{ts escape=js 1="%1" 2="%2" 3="%3" 4=$sortName 5=$caseTypeLabel 6=$caseID}Create link between %1 - %2 (CaseID: %3) and %4 - %5 (CaseID: %6){/ts}";
        {literal}
        $('#subject', $form).val(ts(subject, {1: info['contact_id.sort_name'], 2: info['case_id.case_type_id.title'], 3: $(this).val()}));
      }
    });
  });
</script>
{/literal}
  </div>
