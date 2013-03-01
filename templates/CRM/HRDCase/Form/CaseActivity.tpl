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
{* Template for Case Activity Selector to be shown on Show Case Page *}
        <table cellpadding="0" cellspacing="0" border="0">
        <tr class="columnheader">
            <th field="ActivityType" dataType="String">{ts}Activity Type{/ts}</th>
            <th>{ts}To{/ts}</th>
            <th>{ts}From{/ts}</th>
            <th>{ts}Regarding{/ts}</th>
            <th>{ts}Case{/ts}</th>
            <th>{ts}Type{/ts}</th>
            <th>{ts}Start Date{/ts}</th>
            <th></th>
          <th></th>
        </tr>
        {foreach from=$activities item=activity}
        <tr class="{cycle values="odd-row,even-row"}">
        <td>{$activity.activity_type}</td>
        <td>{$activity.to_contact}</td>
        <td>{$activity.sourceName}</td>
        <td>{$activity.targetName}</td>
        <td>{$form.subject.html}</td>
        <td>{$activity.case_activity_type}</td>
        <td>{$activity.start_date|crmDate}</td>
        <td class="nowrap">{$activity.action}</td>
        </tr>
        {/foreach}
        </table>