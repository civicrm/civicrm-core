{if $rows}
<div class="form-item crm-block crm-form-block crm-contribution-form-block">
     <span class="element-right">{$form.buttons.html}</span>
</div>

<div class="spacer"></div>

<div>
<br />
<table>
  <tr class="columnheader">
    <th>{ts}Display Name{/ts}</th>
    <th>{ts}Amount{/ts}</th>
    <th>{ts}Source{/ts}</th>
    <th>{ts}Receive Date{/ts}</th>
  </tr>

  {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.display_name}</td>
        <td>{$row.amount}</td>
        <td>{$row.source}</td>
        <td>{$row.receive_date}</td>
    </tr>
  {/foreach}
</table>
</div>

<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{else}
   <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {ts}There are no records selected.{/ts}
   </div>
{/if}