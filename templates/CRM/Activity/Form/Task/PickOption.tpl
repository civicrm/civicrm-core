{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form crm-form-block crm-pick-option-form-block">
  <div class="help">{ts}Select Group of Contacts{/ts}</div>
  <table class="form-layout-compressed">
    <tr class="crm-pick-option-form-block-with_contact">
      <td class="label">{$form.with_contact.label}</td>
      <td>{$form.with_contact.html}</td>
    </tr>
    <tr class="crm-pick-option-form-block-assigned_to">
      <td class="label">{$form.assigned_to.label}</td>
      <td>{$form.assigned_to.html}</td>
    </tr>
    <tr class="crm-pick-option-form-block-created_by">
      <td class="label">{$form.created_by.label}</td>
      <td>{$form.created_by.html}</td>
    </tr>
    <tr>
      {include file="CRM/Activity/Form/Task.tpl"}
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
