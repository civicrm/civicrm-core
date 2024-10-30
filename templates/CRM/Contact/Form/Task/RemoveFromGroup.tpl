{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-form-block crm-block crm-contact-task-removefromgroup-form-block">
  <table class="form-layout-compressed">
    <tr class="crm-contact-task-removefromgroupform-block-group_id">
      <td class="label">{if $group.id}{ts}Group{/ts}{else}{$form.group_id.label}{/if}</td>
      <td>{$form.group_id.html}</td>
    </tr>
    <tr>
      <td></td><td>{include file="CRM/Contact/Form/Task.tpl"}</td>
    </tr>
  </table>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
