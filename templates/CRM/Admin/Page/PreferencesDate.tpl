{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
*}
<div id="help">
    {ts}Changing the parameters here globally changes the date parameters for fields in that type across CiviCRM.{/ts}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8}
    {include file="CRM/Admin/Form/PreferencesDate.tpl"}
{else}
    <div class="form-item">
        <table cellpadding="0" cellspacing="0" border="0">
            <tr class="columnheader">
                <th >{ts}Date Class{/ts}</th>
                <th >{ts}Description{/ts}</th>
                <th >{ts}Date Format{/ts}</th>
                <th >{ts}Start Offset{/ts}</th>
                <th >{ts}End Offset{/ts}</th>
                <th ></th>
            </tr>
            {foreach from=$rows item=row}
            <tr class="{cycle values="odd-row,even-row"} {$row.class}">
                <td>{$row.name}</td>
                <td>{$row.description}</td>
                <td class="nowrap">{if !$row.date_format}{ts}Default{/ts}{else}{$row.date_format}{/if}</td>
                <td align="right">{$row.start}</td>
                <td align="right">{$row.end}</td>
                <td><span>{$row.action|replace:'xx':$row.id}</span></td>
            </tr>
            {/foreach}
        </table>
    </div>
{/if}