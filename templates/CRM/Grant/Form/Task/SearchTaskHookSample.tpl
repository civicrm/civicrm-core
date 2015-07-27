{if $rows}
<div class="crm-submit-buttons element-right">{include file="CRM/common/formButtons.tpl" location="top"}</div>

<div class="spacer"></div>

<div>
<br />
<table>
  <tr class="columnheader">
    <td>{ts}Display Name{/ts}</td>
    <td>{ts}Decision Date{/ts}</td>
    <td>{ts}Amount Requested{/ts}</td>
    <td>{ts}Amount Granted{/ts}</td>
  </tr>

  {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"} crm-grant">
        <td class="crm-grant-task-SearchTaskHookSample-form-block-display_name">{$row.display_name}</td>
        <td class="crm-grant-task-SearchTaskHookSample-form-block-decision_date">{$row.decision_date}</td>
        <td class="crm-grant-task-SearchTaskHookSample-form-block-amount_requested">{$row.amount_requested}</td>
        <td class="crm-grant-task-SearchTaskHookSample-form-block-amount_granted">{$row.amount_granted}</td>
    </tr>
  {/foreach}
</table>
</div>

<div class="crm-submit-buttons element-right">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

{else}
   <div class="messages status no-popup">
          <div class="icon inform-icon"></div>
            {ts}There are no records selected.{/ts}
   </div>
{/if}
