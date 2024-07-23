{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is for configuring Scheduled Reminders Table*}
{strip}
  {if $rows and is_array($rows)}
    {include file="CRM/common/enableDisableApi.tpl"}
    {include file="CRM/common/jsortable.tpl"}
  {/if}
  <table id="scheduleReminders" class="display">
    <thead>
    <tr id="options" class="columnheader">
      <th id="sortable">{ts}Title{/ts}</th>
      <th >{ts}Reminder For{/ts}</th>
      <th >{ts}When{/ts}</th>
      <th >{ts}While{/ts}</th>
      <th >{ts}Repeat{/ts}</th>
      <th >{ts}Active?{/ts}</th>
      <th id="nosort"><span class="sr-only">{ts}Actions{/ts}</span></th>
    </tr>
    </thead>
    {if $rows and is_array($rows)}
      {foreach from=$rows item=row}
        <tr id="action_schedule-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
          <td class="crm-scheduleReminders-title crm-editable" data-field="title">{$row.title|smarty:nodefaults|purify}</td>
          <td class="crm-scheduleReminders-value">{$row.entity} - {$row.value}</td>
          <td class="crm-scheduleReminders-description">{if $row.absolute_date}{$row.absolute_date|crmDate}{else}{$row.start_action_offset}&nbsp;{$row.start_action_unit}{if $row.start_action_offset > 1}{ts}(s){/ts}{/if}&nbsp;{$row.start_action_condition}&nbsp;{$row.entityDate}{/if}</td>
          <td class="crm-scheduleReminders-title">{$row.status}</td>
          <td class="crm-scheduleReminders-is_repeat">{if $row.is_repeat eq 1}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}&nbsp;</td>
          <td id="row_{$row.id}_status" class="crm-scheduleReminders-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
      {/foreach}
    {else}
      <tr><td colspan="7">{ts}No Scheduled Reminders have been created.{/ts}</td></tr>
    {/if}
  </table>
{/strip}
