{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form crm-form-block crm-file-on-case-form-block">
  <table class="form-layout-compressed">
    <tr class="crm-file-on-case-form-block-unclosed_cases">
      <td class="label">{$form.unclosed_case_id.label}</td>
      <td>{$form.unclosed_case_id.html}</td>
    </tr>
    <tr>
      {include file="CRM/Activity/Form/Task.tpl"}
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
