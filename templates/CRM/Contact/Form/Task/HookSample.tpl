{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
  <div class="icon inform-icon"></div>
       {ts}There are no records selected for Print.{/ts}
     </div>
{/if}
