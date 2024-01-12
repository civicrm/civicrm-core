{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{ts 1=$totalSelectedCases}Number of selected cases: %1{/ts}

{if $rows}
<div class="crm-block-crm-form-block crm-case-task-form-block">
<table width="30%">
  <tr class="columnheader">
    <th>{ts}Name{/ts}</th>
  </tr>
{foreach from=$rows item=row}
<tr class="{cycle values="odd-row,even-row"} crm-case-task-displayName">
<td>{$row.displayName}</td>
</tr>
{/foreach}
</table>
</div>
{/if}
