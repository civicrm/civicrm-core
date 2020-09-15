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
<div class="crm-submit-buttons element-right">
    {include file="CRM/common/formButtons.tpl" location="top"}
</div>
<div class="spacer"></div>
<div>
<br />
<table>
  <tr class="columnheader">
    <td>{ts}ID{/ts}</td>
    <td>{ts}Type{/ts}</td>
    <td>{ts}Name{/ts}</td>
    <td>{ts}Email{/ts}</t>
  </tr>

{foreach from=$rows item=row}
    <tr class="{cycle values="odd-row,even-row"}">
        <td>{$row.id}</td>
        <td>{$row.contact_type}</td>
        <td>{$row.name}</td>
        <td>{$row.email}</td>
    </tr>
{/foreach}
</table>
</div>

<div class="form-item element-right">
     {include file="CRM/common/formButtons.tpl" location="bottom"}
</div>
{else}
   <div class="messages status no-popup">
  {icon icon="fa-info-circle"}{/icon}
       {ts}There are no records selected for Print.{/ts}
     </div>
{/if}
