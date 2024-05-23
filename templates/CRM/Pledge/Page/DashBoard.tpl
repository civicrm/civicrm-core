{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* CiviPledge DashBoard (launch page) *}

<h3>{ts}Pledge Summary{/ts}</h3>
<table class="report">
<tr class="columnheader-dark">
    <th>&nbsp;</th>
    <th scope="col" colspan="2" class="right" style="padding-right: 10px;">{$previousMonthYear}</th>
    <th scope="col" colspan="2" class="right" style="padding-right: 10px;">{$currentMonthYear}<br /><span class="extra">{ts}(current month){/ts}</span></th>
    <th scope="col" colspan="2" class="right" style="padding-right: 10px;">{$curYear}<br /><span class="extra">{ts}(current year){/ts}</span></th>
    <th scope="col" colspan="2" class="right" style="padding-right: 10px;">{ts}Cumulative{/ts}<br /><span class="extra">{ts}(since inception){/ts}</span></th>
</tr>
<tr>
    <td scope="row"><strong>{ts}Total Pledges{/ts}</strong></td>
    {* prior month *}
        <td class="right">{$previousToDate.Completed.pledge_count}</td><td class="right" style="border-right: 5px double #999999;">{if $previousToDate.Completed.pledge_count}<a href="{$previousToDate.Completed.purl}">{$previousToDate.Completed.pledge_amount}</a>{/if}</td>
    {* current month *}
        <td class="right">{$monthToDate.Completed.pledge_count}</td><td class="right" style="border-right: 5px double #999999;">{if $monthToDate.Completed.pledge_count}<a href="{$monthToDate.Completed.purl}">{$monthToDate.Completed.pledge_amount}</a>{/if}</td>
    {* current year *}
        <td class="right">{$yearToDate.Completed.pledge_count}</td><td class="right" style="border-right: 5px double #999999;">{if $yearToDate.Completed.pledge_count}<a href="{$yearToDate.Completed.purl}">{$yearToDate.Completed.pledge_amount}</a>{/if}</td>
    {* cumulative *}
        <td class="right">{$startToDate.Completed.pledge_count}</td><td class="right">{if $startToDate.Completed.pledge_count}<a href="{$startToDate.Completed.purl}">{$startToDate.Completed.pledge_amount}</a>{/if}</td>
</tr>
<tr>
    <td scope="row"><strong>{ts}Payments Received{/ts}</strong></td>
    {* prior month *}
     <td class="right">{$previousToDate.Completed.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $previousToDate.Completed.received_count}<a href="{$previousToDate.Completed.url}">{$previousToDate.Completed.received_amount}</a>{/if}</td>
    {* current month *}
        <td class="right">{$monthToDate.Completed.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $monthToDate.Completed.received_count}<a href="{$monthToDate.Completed.url}">{$monthToDate.Completed.received_amount}</a>{/if}</td>
    {* current year *}
        <td class="right">{$yearToDate.Completed.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $yearToDate.Completed.received_count}<a href="{$yearToDate.Completed.url}">{$yearToDate.Completed.received_amount}</a>{/if}</td>
    {* cumulative *}
        <td class="right">{$startToDate.Completed.received_count}</td><td class="right">{if $startToDate.Completed.received_count}<a href="{$startToDate.Completed.url}">{$startToDate.Completed.received_amount}</a>{/if}</td>
</tr>
<tr>
    <td scope="row"><strong>{ts}Balance Due{/ts}</strong></td>
    {* prior month *}
        <td class="right">{$previousToDate.Pending.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $previousToDate.Pending.received_count}<a href="{$previousToDate.Pending.url}">{$previousToDate.Pending.received_amount}</a>{/if}</td>
    {* current month *}
        <td class="right">{$monthToDate.Pending.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $monthToDate.Pending.received_count}<a href="{$monthToDate.Pending.url}">{$monthToDate.Pending.received_amount}</a>{/if}</td>
    {* current year *}
        <td class="right">{$yearToDate.Pending.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $yearToDate.Pending.received_count}<a href="{$yearToDate.Pending.url}">{$yearToDate.Pending.received_amount}</a>{/if}</td>
    {* cumulative *}
        <td class="right">{$startToDate.Pending.received_count}</td><td class="right">{if $startToDate.Pending.received_count}<a href="{$startToDate.Pending.url}">{$startToDate.Pending.received_amount}</a>{/if}</td>
</tr>
<tr>
    <td scope="row"><strong>{ts}Past Due{/ts}</strong></td>
    {* prior month *}
        <td class="right">{$previousToDate.Overdue.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $previousToDate.Overdue.received_count}<a href="{$previousToDate.Overdue.url}">{$previousToDate.Overdue.received_amount}</a>{/if}</td>
    {* current month *}
        <td class="right">{$monthToDate.Overdue.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $monthToDate.Overdue.received_count}<a href="{$monthToDate.Overdue.url}">{$monthToDate.Overdue.received_amount}</a>{/if}</td>
    {* current year *}
        <td class="right">{$yearToDate.Overdue.received_count}</td><td class="right" style="border-right: 5px double #999999;">{if $yearToDate.Overdue.received_count}<a href="{$yearToDate.Overdue.url}">{$yearToDate.Overdue.received_amount}</a>{/if}</td>
    {* cumulative *}
        <td class="right">{$startToDate.Overdue.received_count}</td><td class="right">{if $startToDate.Overdue.received_count}<a href="{$startToDate.Overdue.url}">{$startToDate.Overdue.received_amount}</a>{/if}</td>
</tr>
</table>

<div class="spacer"></div>

{if $pager->_totalItems}
    <h3>{ts}Recent Pledges{/ts}</h3>
    <div>
        {include file="CRM/Pledge/Form/Selector.tpl" context="dashboard"}
    </div>
{/if}
