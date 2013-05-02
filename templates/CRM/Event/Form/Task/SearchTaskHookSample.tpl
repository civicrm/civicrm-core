{if $rows}
<div class="crm-submit-buttons">
     <span class="element-right">{include file="CRM/common/formButtons.tpl" location="top"}</span>
</div>

<div class="spacer"></div>
<div>
<br />
<table>
  <tr class="columnheader">
    <th>{ts}Display Name{/ts}</th>
    <th>{ts}Amount{/ts}</th>
    <th>{ts}Register Date{/ts}</th>
    <th>{ts}Source{/ts}</th>
  </tr>

  {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td class="crm-event-searchtaskhooksample-display_name">{$row.display_name}</td>
        <td class="crm-event-searchtaskhooksample-amount">{$row.amount}</td>
        <td class="crm-event-searchtaskhooksample-register_date">{$row.register_date}</td>
        <td class="crm-event-searchtaskhooksample-source">{$row.source}</td>
    </tr>
  {/foreach}
</table>
</div>

<div class="crm-submit-buttons">
     <span class="element-right">{include file="CRM/common/formButtons.tpl" location="bottom"}</span>
</div>

{else}
   <div class="messages status no-popup">
      <div class="icon inform-icon"></div>&nbsp;{ts}There are no records selected.{/ts}
   </div>
{/if}