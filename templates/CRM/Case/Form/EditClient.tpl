{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* template for assigning the current case to another client*}
<div class="crm-block crm-form-block crm-case-editclient-form-block">
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon} {ts 1=$currentClientName}This is case is currently assigned to %1.{/ts}
  </div>
  <div class="crm-form-block">
    <table class="form-layout-compressed">
      <tr class="crm-case-editclient-form-block-change_client_id">
        <td class="label">
          {$form.reassign_contact_id.label}
        </td>
        <td>
          {$form.reassign_contact_id.html}
        </td>
    </table>
    <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
  </div>
</div>
