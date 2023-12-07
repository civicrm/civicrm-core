{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of Grant delete  *}
<div class="crm-block crm-form-block crm-grant-task-delete-form-block">
  <div class="messages status no-popup">
          {icon icon="fa-info-circle"}{/icon}
          {ts}Are you sure you want to delete the selected Grants? This delete operation cannot be undone and will delete all transactions associated with these grants.{/ts}
          <p>{include file="CRM/Grant/Form/Task.tpl"}</p>
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
</div>
