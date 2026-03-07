{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirm contact deletion *}
<div class="crm-block crm-form-block crm-contact-task-delete-form-block">
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {if $restore}
      {ts}Are you sure you want to restore the selected contacts? The contacts and all related data will be fully restored.{/ts}
    {elseif $trash}
      {ts}The contacts and all related data will be moved to trash. Only users with the relevant permission will be able to restore it.{/ts}
      {ts}Are you sure you want to delete the selected contacts?{/ts}
    {else}
      {ts}The contacts and all related data will be permanently removed.{/ts}
      {ts}Are you sure you want to delete the selected contacts?{/ts} {ts}This action cannot be undone.{/ts}
    {/if}
  </div>
  <p>{include file="CRM/Contact/Form/Task.tpl"}</p>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
</div>
