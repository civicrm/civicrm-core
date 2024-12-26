{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="crm-block crm-form-block crm-event-manage-repeat-form-block">
{include file="CRM/Core/Form/RecurringEntity.tpl" recurringFormIsEmbedded=false}
{if $rows}
<details class="crm-block crm-manage-events crm-accordion-bold" open>
  <summary>{ts}Connected Repeating Events{/ts}</summary>
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
</details>
{/if}
</div>
