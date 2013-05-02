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
{if $rows}
<div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

<div class="spacer"></div>

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
          <div class="icon inform-icon"></div>
            {ts}There are no records selected.{/ts}
   </div>
{/if}