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
<div class="form-item crm-block crm-form-block crm-contribution-form-block">
     <span class="element-right">{$form.buttons.html}</span>
</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <th>{ts}Name{/ts}</th>
    <th>{ts}Amount{/ts}</th>
    <th>{ts}Type{/ts}</th>
    <th>{ts}Contribution Source{/ts}</th>
    <th>{ts}Contribution Date{/ts}</th>
    <th>{ts}Thank-you Sent{/ts}</th>
    <th>{ts}Status{/ts}</th>
    <th>{ts}Premium{/ts}</th>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"} crm-contribution">
        <td class="crm-contribution-sort_name">{$row.sort_name}</td>
        <td class="right bold crm-contribution-total_amount" nowrap>{$row.total_amount|crmMoney}</td>
        <td class="crm-contribution-type crm-contribution-{$row.financial_type} crm-financial-type crm-contribution-{$row.financial_type}">{$row.financial_type}</td>
        <td class="crm-contribution-contribution_source">{$row.contribution_source}</td>
        <td class="crm-contribution-receive_date">{$row.receive_date|truncate:10:''|crmDate}</td>
        <td class="crm-contribution-thankyou_date">{$row.thankyou_date|truncate:10:''|crmDate}</td>
        <td class="crm-contribution-status crm-contribution-status_{$row.contribution_status_id}">
            {$row.contribution_status_id}<br />
            {if $row.contribution_cancel_date}
                {$row.contribution_cancel_date|truncate:10:''|crmDate}
            {/if}
        </td>
        <td class="crm-contribution-product_name">{$row.product_name}</td>
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
