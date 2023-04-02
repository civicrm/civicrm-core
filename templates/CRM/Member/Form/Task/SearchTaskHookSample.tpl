{if $rows}
<div class="crm-submit-buttons element-right">
   {include file="CRM/common/formButtons.tpl" location="top"}
</div>

<div class="spacer"></div>

<div>
<br />
<table>
  <tr class="columnheader">
    <th>{ts}Display Name{/ts}</th>
    <th>{ts}Membership Start Date{/ts}</th>
    <th>{ts}Membership Expiration Date{/ts}</th>
    <th>{ts}Membership Source{/ts}</th>
  </tr>

  {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"} crm-membership">
        <td class="crm-membership-display_name">{$row.display_name}</td>
        <td class="crm-membership-start_date">{$row.start_date}</td>
        <td class="crm-membership-end_date">{$row.end_date}</td>
        <td class="crm-membership-source">{$row.source}</td>
    </tr>
  {/foreach}
</table>
</div>
<div class="crm-submit-buttons element-right">
   {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{else}
   <div class="messages status no-popup">
            {icon icon="fa-info-circle"}{/icon}
               {ts}There are no records selected.{/ts}
   </div>
{/if}
