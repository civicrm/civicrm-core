{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.6                                                |
 +--------------------------------------------------------------------+
 | Copyright (C) 2011 Marty Wright                                    |
 | Licensed to CiviCRM under the Academic Free License version 3.0.   |
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
{* this template is for configuring relative date filters*}
<div class="help">
  {ts}You can configure relative date filters for your searches and reports.{/ts}
</div>
{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 16384}
  {include file="CRM/Admin/Form/DateFilter.tpl"}
{else}

  {if $rows}
    <div id="date-filters">
      {strip}
      {* handle enable/disable actions*}
      {include file="CRM/common/enableDisableApi.tpl"}
        <table id="dateFilter" class="row-highlight">
          <thead>
          <tr class="columnheader">
            <th>{ts}Name{/ts}</th>
            <th>{ts}Description{/ts}</th>
            <th>{ts}Order{/ts}</th>
            <th>{ts}Reserved?{/ts}</th>
            <th></th>
          </tr>
          </thead>
          {foreach from=$rows item=row}
            <tr id="option_value-{$row.id}" class="crm-dateFilter crm-entity {cycle values="odd-row,even-row"} {$row.class} {if NOT $row.is_active} disabled{/if}">
              <td class="crm-dateFilter-name">{$row.label}</td>
              <td class="crm-dateFilter-name">{$row.description}</td>
              <td class="crm-dateFilter-order nowrap">{$row.weight}</td>
              <td class="crm-dateFilter-is_reserved">{if $row.is_reserved eq 1}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}
                &nbsp;</td>
              <td>{$row.action|replace:'xx':$row.id}</td>
            </tr>
          {/foreach}
        </table>
      {/strip}

      <div class="action-link">
        <a href="{crmURL q="action=add&reset=1"}" id="newDateFilter" class="button"><span><div
              class="icon ui-icon-circle-plus"></div>{ts}Add Relative Date Filter{/ts}</span></a>
      </div>
    </div>
  {else}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
      {capture assign=crmURL}{crmURL p='civicrm/admin/dateFilter' q="action=add&reset=1"}{/capture}
      {ts 1=$crmURL}There are no Relative Date Filters configured. You can<a href='%1'>add one</a>.{/ts}
    </div>
  {/if}
{/if}
