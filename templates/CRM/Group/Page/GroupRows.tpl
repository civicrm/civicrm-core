{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{foreach from=$current_rows item=row}

<tr class="{cycle values="odd-row,even-row"}{if NOT $row.is_active} disabled{/if}">
<td>{if NOT $row.is_active}hey{/if}{section name = "indentation" loop = $row.level}>{/section}{$row.title}</td>
    <td>{$row.id}</td>
    <td>
    {$row.description|mb_truncate:80:"...":true}
    </td>
    <td>{$row.visibility}</td>
    <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
    </tr>
{/foreach}
