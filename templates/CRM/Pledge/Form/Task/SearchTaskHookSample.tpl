{if $rows}
<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

<div class="spacer"></div>

<div>
<br />
<table>
  <tr class="columnheader">
    <th>{ts}Display Name{/ts}</th>
    <th>{ts}Pledge Amount{/ts}</th>
    <th>{ts}Pledge made{/ts}</th>
  </tr>

  {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td class="crm-pledge-display_name">{$row.display_name}</td>
        <td class="crm-pledge-amount">{$row.amount}</td>
        <td class="crm-pledge-create_date">{$row.create_date}</td>
    </tr>
  {/foreach}
</table>
</div>

<div class="form-item">
     <span class="element-right">{$form.buttons.html}</span>
</div>

{else}
   <div class="messages status no-popup">
          <dt><div class="icon inform-icon"></div>
            {ts}There are no records selected.{/ts}
      </dl>
   </div>
{/if}
