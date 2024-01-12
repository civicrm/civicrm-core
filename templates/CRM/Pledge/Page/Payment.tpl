{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $action eq 2} {* update *}
    {include file="CRM/Pledge/Form/Payment.tpl"}
{else}
{if $context eq 'dashboard'}{assign var='context' value='pledgeDashboard'}{/if}
<table class="nestedSelector">
  <tr class="columnheader">
    <th>{ts}Amount Due{/ts}</th>
    <th>{ts}Due Date{/ts}</th>
    <th>{ts}Amount Paid{/ts}</th>
    <th>{ts}Paid Date{/ts}</th>
    <th>{ts}Last Reminder{/ts}</th>
    <th>{ts}Reminders Sent{/ts}</th>
    <th colspan="2">{ts}Status{/ts}</th>
  </tr>

  {foreach from=$rows item=row}
   <tr class="{cycle values="odd-row,even-row"} {if $row.status eq 'Overdue'} status-overdue{/if}">
    <td class="right">{$row.scheduled_amount|crmMoney:$row.currency}</td>
    <td>{$row.scheduled_date|truncate:10:''|crmDate}</td>
    <td class="right">{$row.total_amount|crmMoney:$row.currency}</td>
    <td>{$row.receive_date|truncate:10:''|crmDate}</td>
    <td>{$row.reminder_date|truncate:10:''|crmDate}</td>
    <td class="right">{if $row.reminder_count}{$row.reminder_count}{/if}</td>
    <td {if ! ($permission EQ 'edit' and ($row.status eq 'Pending' or $row.status eq 'Overdue' or $row.status eq 'Completed'))} colspan="2"{/if} >{$row.label}</td>
{if $context neq 'user'}
    {if $permission EQ 'edit' and ($row.status eq 'Pending' or $row.status eq 'Overdue' or $row.status eq 'Completed')}
        <td class="nowrap">
        {if $row.status eq 'Completed'} {* Link to view contribution record for completed payment.*}
            {capture assign=viewContribURL}{crmURL p="civicrm/contact/view/contribution" q="reset=1&id=`$row.contribution_id`&cid=`$contactId`&action=view&context=`$context`"}{/capture}
            <a class="crm-hover-button action-item" href="{$viewContribURL}">{ts}View Payment{/ts}</a>
        {else} {* Links to record / submit a payment. *}
            {capture assign=newContribURL}{crmURL p="civicrm/contact/view/contribution" q="reset=1&action=add&cid=`$contactId`&context=`$context`&ppid=`$row.id`"}{/capture}
            <a class="open-inline-noreturn crm-hover-button action-item" href="{$newContribURL}">{ts}Record Payment{/ts}</a>
            {if $newCredit}
              {capture assign=newCreditURL}{crmURL p="civicrm/contact/view/contribution" q="reset=1&action=add&cid=`$contactId`&ppid=`$row.id`&context=`$context`&mode=live"}{/capture}
              <a class="open-inline-noreturn action-item crm-hover-button" href="{$newCreditURL}">{ts}Credit Card Payment{/ts}</a>
            {/if}
            {capture assign=editURL}{crmURL p="civicrm/pledge/payment" q="reset=1&action=update&cid=`$contactId`&context=`$context`&ppId=`$row.id`"}{/capture}
            <a class="crm-hover-button action-item" href="{$editURL}">{ts}Edit Scheduled Payment{/ts}</a>
        {/if}
        </td>
    {/if}
{/if}
   </tr>
  {/foreach}
</table>
{/if}
