{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Confirmation of contact deletes  *}
<div class="crm-block crm-form-block crm-contact-task-delete-form-block">
<div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
      {if $restore}
    {ts}Are you sure you want to restore the selected contact(s)? The contact(s) and all related data will be fully restored.{/ts}
      {elseif $trash}
        {ts}Are you sure you want to delete the selected contact(s)?{/ts} {ts}The contact(s) and all related data will be moved to trash and only users with the relevant permission will be able to restore it.{/ts}
      {else}
        {ts}Are you sure you want to delete the selected contact(s)?{/ts} {ts}The contact(s) and all related data will be permanently removed.{/ts} {ts}This action cannot be undone.{/ts}
      {/if}
  </div>


    <h3>{include file="CRM/Contact/Form/Task.tpl"}</h3>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location=''}</div>
</div>
