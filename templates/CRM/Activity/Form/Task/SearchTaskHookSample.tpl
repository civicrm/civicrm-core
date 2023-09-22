{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $rows}
<div>
<br />
<table>
  <tr class="columnheader">
    <td>{ts}Display Name{/ts}</td>
    <td>{ts}Subject{/ts}</th>
    <td>{ts}Activity Type{/ts}</td>
    <td>{ts}Activity Date{/ts}</td>
  </tr>
  {foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.display_name}</td>
        <td>{$row.subject}</td>
        <td>{$row.activity_type}</td>
        <td>{$row.activity_date}</td>
    </tr>
  {/foreach}
</table>
</div>

<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>

{else}
   <div class="messages status no-popup">
     {icon icon="fa-info-circle"}{/icon}
     {ts}There are no records selected.{/ts}
   </div>
{/if}
