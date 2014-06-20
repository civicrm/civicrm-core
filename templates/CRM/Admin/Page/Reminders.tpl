{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
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
{* this template is for configuring Scheduled Reminders Table*}
{strip}
  {if $rows and is_array($rows)}
    {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/crmeditable.tpl"}
    {include file="CRM/common/jsortable.tpl"}
  {/if}
  <table id="scheduleReminders" class="display">
    <thead>
    <tr id="options" class="columnheader">
      <th class="sortable">{ts}Title{/ts}</th>
      <th >{ts}Reminder For{/ts}</th>
      <th >{ts}When{/ts}</th>
      <th >{ts}While{/ts}</th>
      <th >{ts}Repeat{/ts}</th>
      <th >{ts}Active?{/ts}</th>
      <th class="hiddenElement"></th>
      <th ></th>
    </tr>
    </thead>
    {if $rows and is_array($rows)}
      {foreach from=$rows item=row}
        <tr id="action_schedule-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td class="crm-scheduleReminders-title crm-editable" data-field="title">{$row.title}</td>
          <td class="crm-scheduleReminders-value">{$row.entity} - {$row.value}</td>
          <td class="crm-scheduleReminders-description">{if $row.absolute_date}{$row.absolute_date|crmDate}{else}{$row.start_action_offset}&nbsp;{$row.start_action_unit}{if $row.start_action_offset > 1}{ts}(s){/ts}{/if}&nbsp;{$row.start_action_condition}&nbsp;{$row.entityDate}{/if}</td>
          <td class="crm-scheduleReminders-title">{$row.status}</td>
          <td class="crm-scheduleReminders-is_repeat">{if $row.is_repeat eq 1}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}&nbsp;</td>
          <td id="row_{$row.id}_status" class="crm-scheduleReminders-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
          <td class="hiddenElement"></td>
        </tr>
      {/foreach}
    {else}
      <tr><td colspan="8">{ts}No Scheduled Reminders have been created.{/ts}</td></tr>
    {/if}
  </table>
{/strip}


