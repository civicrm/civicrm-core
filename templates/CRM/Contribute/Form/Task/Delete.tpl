{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirm contribution deletion *}
<div class="crm-block crm-form-block crm-contribution-task-delete-form-block">
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts}This delete operation cannot be undone and will delete all transactions and activity associated with these contributions.{/ts} {ts}This action cannot be undone.{/ts}
    {ts}Are you sure you want to delete the selected contributions?{/ts}
    <p>{include file="CRM/Contribute/Form/Task.tpl"}</p>
  </div>
  <div class="form-item">
   {$form.buttons.html}
  </div>
</div>
