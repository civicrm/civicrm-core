{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for confirmation of delete for event  *}
<div class="crm-block crm-form-block crm-event-manage-delete-form-block">
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    <div>
       {if $isTemplate}
         {ts}Warning: Deleting this event template will also delete associated Event Registration Page and Event Fee configurations.{/ts} {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
       {else}
            {ts}Warning: Deleting this event will also delete associated Event Registration Page and Event Fee configurations.{/ts} {ts}This action cannot be undone.{/ts} {ts}Do you want to continue?{/ts}
       {/if}
    </div>
  </div>
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>
