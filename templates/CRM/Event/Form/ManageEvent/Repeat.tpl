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
<div class="crm-block crm-form-block crm-event-manage-repeat-form-block">
{include file="CRM/Core/Form/RecurringEntity.tpl" recurringFormIsEmbedded=false}
{if $rows}
<div class="crm-block crm-manage-events crm-accordion-wrapper">
  <div class="crm-accordion-header">{ts}Connected Repeating Events{/ts}</div>
  <div class="crm-accordion-body">
  {strip}
  {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
      <thead>
      <tr>
        <th>{ts}Event{/ts}</th>
        <th>{ts}Public?{/ts}</th>
        <th>{ts}Starts{/ts}</th>
        <th>{ts}Ends{/ts}</th>
        <th>{ts}Active?{/ts}</th>
        <th>{ts}Event Link{/ts}</th>
        <th class="hiddenElement"></th>
        <th class="hiddenElement"></th>
      </tr>
      </thead>
      {foreach from=$rows key=keys item=row}
        {if $keys neq 'tab'}
          {if $currentEventId eq $row.id}
              {assign var="highlight" value=" status bold"}
          {else}
              {assign var="highlight" value=""}
          {/if}
          <tr class="row_{$row.id}{if NOT $row.is_active} disabled{/if}">
          <td class="crm-event_{$row.id}{$highlight}">
            <a href="{crmURL p='civicrm/event/info' q="id=`$row.id`&reset=1"}"
               title="{ts}View event info page{/ts}" class="bold">{$row.title}</a>&nbsp;&nbsp;({ts}ID:{/ts} {$row.id})
          </td>
          <td class="crm-event-is_public{$highlight}">{if $row.is_public eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td class="crm-event-start_date{$highlight}" data-order="{$row.start_date|crmDate:'%Y-%m-%d'}">{$row.start_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
              <td class="crm-event-end_date{$highlight}" data-order="{$row.end_date|crmDate:'%Y-%m-%d'}">{$row.end_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
          <td class="crm-event_status{$highlight}" id="row_{$row.id}_status">
            {if $row.is_active eq 1}{ts}Yes{/ts} {else} {ts}No{/ts} {/if}
          </td>
          <td class="{$highlight}">
            <a class="action-item no-popup crm-hover-button" href="{crmURL p="civicrm/event/manage/settings" q="reset=1&action=update&id=`$row.id`"}">{ts}Settings{/ts}</a>
          </td>
          <td class="crm-event-start_date hiddenElement">{$row.start_date|crmDate}</td>
          <td class="crm-event-end_date hiddenElement">{$row.end_date|crmDate}</td>
        </tr>
        {/if}
      {/foreach}
    </table>
  {/strip}
  </div>
</div>
{/if}
</div>