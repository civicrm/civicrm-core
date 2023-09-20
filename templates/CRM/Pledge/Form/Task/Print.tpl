{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<p>

{if $rows}
<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <th>{ts}Name{/ts}</th>
    <th>{ts}Amount{/ts}</th>
    <th>{ts}Create Date{/ts}</th>
    <th>{ts}To Be Paid{/ts}</th>
    <th>{ts}Beginning Date{/ts}</th>
    <th>{ts}Status{/ts}</th>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td class="crm-pledge-sort_name" >{$row.sort_name}</td>
        <td class="crm-pledge-pledge_amount">{$row.pledge_amount|crmMoney}</td>
        <td class="crm-pledge-pledge_create_date">{$row.pledge_create_date|truncate:10:''|crmDate}</td>
        <td class="crm-pledge-pledge_frequency_interval">{$row.pledge_frequency_interval} {$row.pledge_frequency_unit|capitalize:true}(s) </td>
        <td class="crm-pledge-.pledge_start_date">{$row.pledge_start_date|truncate:10:''|crmDate}</td>
        <td class="crm-pledge-pledge_status">{$row.pledge_status_id}</td>
    </tr>
{/foreach}
</table>

<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{else}
   <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
