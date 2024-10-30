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
<div class="crm-submit-buttons element-right">{$form.buttons.html}</div>
<div class="spacer"></div>
<br />
<p>
<table>
  <tr class="columnheader">
    <td>{ts}Name{/ts}</td>
    <td>{ts}Status{/ts}</td>
    <td>{ts}Type{/ts}</td>
    <td>{ts}Amount Requested{/ts}</td>
    <td>{ts}Amount Requested(orig. currency){/ts}</td>
    <td>{ts}Amount Granted{/ts}</td>
    <td>{ts}Application Received{/ts}</td>
    <td>{ts}Money Transferred{/ts}</td>
  </tr>
{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.sort_name}</td>
        <td>{$row.grant_status}</td>
        <td>{$row.grant_type}</td>
        <td>{$row.grant_amount_total|crmMoney}</td>
        <td>{$row.grant_amount_requested|crmMoney}</td>
        <td>{$row.grant_amount_granted|crmMoney}</td>
        <td>{$row.grant_application_received_date|truncate:10:''|crmDate}</td>
        <td>{$row.grant_money_transfer_date|truncate:10:''|crmDate}</td>
    </tr>
{/foreach}
</table>

<div class="crm-submit-buttons element-right">{$form.buttons.html}</div>

{else}
   <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
        {ts}There are no records selected for Print.{/ts}
   </div>
{/if}
