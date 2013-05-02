{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
{if $action eq 2 } {* update *}
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
   <tr class="{cycle values="odd-row,even-row"} {if $row.status eq 'Overdue' } status-overdue{/if}">
    <td class="right">{$row.scheduled_amount|crmMoney:$row.currency}</td>
    <td>{$row.scheduled_date|truncate:10:''|crmDate}</td>
    <td class="right">{$row.total_amount|crmMoney:$row.currency}</td>
    <td>{$row.receive_date|truncate:10:''|crmDate}</td>
    <td>{$row.reminder_date|truncate:10:''|crmDate}</td>
    <td class="right">{if $row.reminder_count}{$row.reminder_count}{/if}</td>
    <td {if ! ($permission EQ 'edit' and ($row.status eq 'Pending' or $row.status eq 'Overdue' or $row.status eq 'Completed')) } colspan="2"{/if} >{$row.label}</td>
{if $context neq user}
    {if $permission EQ 'edit' and ($row.status eq 'Pending' or $row.status eq 'Overdue' or $row.status eq 'Completed') }
        <td>
        {if $row.status eq 'Completed'} {* Link to view contribution record for completed payment.*}
            {capture assign=viewContribURL}{crmURL p="civicrm/contact/view/contribution" q="reset=1&id=`$row.contribution_id`&cid=`$contactId`&action=view&context=`$context`"}{/capture}
            {ts 1=$viewContribURL}<a href='%1'>View Payment</a>{/ts}
        {else} {* Links to record / submit a payment. *}
            {capture assign=newContribURL}{crmURL p="civicrm/contact/view/contribution" q="reset=1&action=add&cid=`$contactId`&context=`$context`&ppid=`$row.id`"}{/capture}
            {ts 1=$newContribURL}<a href='%1'>Record Payment (Check, Cash, EFT ...)</a>{/ts}
            {if $newCredit}
              <br/>
              {capture assign=newCreditURL}{crmURL p="civicrm/contact/view/contribution" q="reset=1&action=add&cid=`$contactId`&ppid=`$row.id`&context=`$context`&mode=live"}{/capture}
              {ts 1=$newCreditURL}<a href='%1'>Submit Credit Card Payment</a>{/ts}
            {/if}
            <br/>
            {capture assign=editURL}{crmURL p="civicrm/pledge/payment" q="reset=1&action=update&cid=`$contactId`&context=`$context`&ppId=`$row.id`"}{/capture}
            {ts 1=$editURL}<a href='%1'>Edit Scheduled Payment</a>{/ts}
        {/if}
        </td>
    {/if}
{/if}
   </tr>
  {/foreach}
</table>
{/if}