{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
{strip}
{if $rows}
  <table class="nestedActivitySelector" data-params='{$data_params}'>
    <tr class="columnheader">
      <th>{ts}Date{/ts}</th>
      <th>{ts}Subject{/ts}</th>
      <th>{ts}Type{/ts}</th>
      <th>{ts}With{/ts}</th>
      <th>{ts}Reporter / Assignee{/ts}</th>
      <th>{ts}Status{/ts}</th>
      <th></th>
    </tr>

    {counter start=0 skip=1 print=false}
    {foreach from=$rows item=row}
    <tr class="{$row.class}">
      <td class="crm-case-display_date">{$row.display_date}</td>
      <td class="crm-case-subject">{$row.subject}</td>
      <td class="crm-case-type">{$row.type}</td>
      <td class="crm-case-with_contacts">{$row.with_contacts}</td>
      <td class="crm-case-reporter">{$row.reporter}</td>
      <td class="crm-case-status">{$row.status}</td>
      <td style="white-space: nowrap;">{$row.links}</td>
    </tr>
    {/foreach}

  </table>
{else}
    <strong>{ts}There are no activities defined for this case.{/ts}</strong>
{/if}
{/strip}

{include file="CRM/Case/Form/ActivityToCase.tpl"}
