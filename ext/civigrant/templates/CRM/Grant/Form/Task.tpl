{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{if $totalSelectedGrants}
    {ts 1=$totalSelectedGrants}Number of selected grants: %1{/ts}
{/if}
{if $rows}
<div class="form-item">
<table width="30%">
  <tr class="columnheader">
    <td>{ts}Name{/ts}</td>
  </tr>
{foreach from=$rows item=row}
<tr class="{cycle values="odd-row,even-row"}">
<td>{$row.displayName}</td>
</tr>
{/foreach}
</table>
</div>
{/if}
