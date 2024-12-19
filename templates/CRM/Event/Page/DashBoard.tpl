{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* CiviEvent DashBoard (launch page) *}
{capture assign=newEventURL}{crmURL p="civicrm/event/add" q="action=add&reset=1"}{/capture}
{capture assign=configPagesURL}{crmURL p="civicrm/event/manage" q="reset=1"}{/capture}

{if $eventSummary.total_events}
    <a href="{$configPagesURL}" class="button no-popup"><span><i class="crm-i fa-th-list" aria-hidden="true"></i> {ts}Manage Events{/ts}</span></a>
    <a href="{$newEventURL}" class="button"><span><i class="crm-i fa-calendar-plus-o" aria-hidden="true"></i> {ts}New Event{/ts}</span></a>
    <div class="clear">&nbsp;</div>
    <h3 id="crm-event-dashboard-heading">{ts}Event Summary{/ts}
      {help id="id-event-intro"}
    </h3>
    <div class="crm-clearfix">
      {include file="CRM/Event/Page/iCalLinks.tpl"}
    </div>
    {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
    <thead>
    <tr>
  <th>{ts}Event{/ts}</th>
  <th>{ts}ID{/ts}</th>
  <th>{ts}Type{/ts}</th>
  <th id="nosort">{ts}Public{/ts}</th>
  <th id="nosort">{ts}Date(s){/ts}</th>
  <th id="nosort">{ts}Participants{/ts}</th>
        {if $actionColumn}<th></th>{/if}
    </tr>
    </thead>
    <tbody>
    {foreach from=$eventSummary.events item=values key=id}
    <tr class="crm-event_{$id}">
        <td class="crm-event-eventTitle"><a href="{crmURL p="civicrm/event/info" q="reset=1&id=`$id`"}" title="{ts}View event info page{/ts}">{$values.eventTitle|smarty:nodefaults|purify}</a>
            {if $values.is_repeating_event}
                <br/>
                {if $values.is_repeating_event eq $id}
                    <span>{ts}Repeating Event{/ts} - ({ts}Parent{/ts})</span>
                {else}
                    <span>{ts}Repeating Event{/ts} - ({ts}Child{/ts})</span>
                {/if}
            {/if}
        </td>
        <td class="crm-event-id">{$id}</td>
        <td class="crm-event-eventType">{$values.eventType}</td>
        <td class="crm-event-isPublic">{$values.isPublic}</td>
        <td class="nowrap crm-event-startDate">{$values.startDate}&nbsp;{if $values.endDate}to{/if}&nbsp;{$values.endDate}</td>
        <td class="right crm-event-participants_url">
            {if $values.participants and $values.participants_url}
    <a href="{$values.participants_url}" title="{ts 1=$eventSummary.countedStatusANDRoles}List %1 participants{/ts}">{ts}Counted{/ts}:&nbsp;{$values.participants}</a>
      {else}
    {ts}Counted{/ts}:&nbsp;{$values.participants}
      {/if}

      {if $values.notCountedParticipants and $values.notCountedParticipants_url}
    <a href="{$values.notCountedParticipants_url}" title="{ts 1=$eventSummary.nonCountedStatusANDRoles}List %1 participants{/ts}">{ts}Not&nbsp;Counted{/ts}:&nbsp;{$values.notCountedParticipants}</a><hr />
      {else}
    {ts}Not&nbsp;Counted{/ts}:&nbsp;{$values.notCountedParticipants}<hr />
      {/if}

      {if $values.notCountedDueToStatus and $values.notCountedDueToStatus_url}
    <a href="{$values.notCountedDueToStatus_url}" title="{ts 1=$eventSummary.nonCountedStatus}List %1 participants{/ts}">{ts}Not&nbsp;Counted&nbsp;Due&nbsp;To&nbsp;Status{/ts}:&nbsp;{$values.notCountedDueToStatus}</a><hr />
      {else}
    {ts}Not&nbsp;Counted&nbsp;Due&nbsp;To&nbsp;Status{/ts}:&nbsp;{$values.notCountedDueToStatus}<hr />
      {/if}

            {if $values.notCountedDueToRole and $values.notCountedDueToRole_url}
    <a href="{$values.notCountedDueToRole_url}" title="{ts 1=$eventSummary.nonCountedRoles}List %1 participants{/ts}">{ts}Not&nbsp;Counted&nbsp;Due&nbsp;To&nbsp;Role{/ts}:&nbsp;{$values.notCountedDueToRole}</a><hr />
      {else}
    {ts}Not&nbsp;Counted&nbsp;Due&nbsp;To&nbsp;Role{/ts}:&nbsp;{$values.notCountedDueToRole}<hr />
      {/if}

            {foreach from=$values.statuses item=class}
                {if $class}
                    {foreach from=$class item=status}
                        <a href="{$status.url}" title="{ts 1=$status.label}List %1 participants{/ts}">{$status.label}: {$status.count}</a>
                    {/foreach}
                    <hr />
                {/if}
            {/foreach}
            {if $values.maxParticipants}{ts 1=$values.maxParticipants}(max %1){/ts}{/if}
        </td>
      {if $actionColumn}
        <td class="crm-event-isMap">
          {if $values.isMap}
            <a href="{$values.isMap}" title="{ts}Map event location{/ts}"><i class="crm-i fa-map-marker" aria-hidden="true"></i> {ts}Map{/ts}</a>
            &nbsp;|&nbsp;
          {/if}
          {if $values.configure}
            <div class="crm-configure-actions">
                <span id="{$id}" class="btn-slide crm-hover-button">{ts}Configure{/ts}
                  <ul class="panel" id="panel_info_{$id}">
                    {foreach from=$eventSummary.tab key=k item=v}
                      {assign var="fld" value=$v.field}
                      {if empty($values.$fld)}{assign var="status" value="disabled"}{else}{assign var="status" value="enabled"}{/if}
                      {* Schedule Reminders requires a different query string. *}
                      {if $v.url EQ 'civicrm/event/manage/reminder'}
                        <li><a title="{$v.title|escape}" class="action-item crm-hover-button no-popup {$status}"
                            href="{crmURL p="`$v.url`" q="reset=1&action=browse&setTab=1&id=`$id`"}">{$v.title}</a></li>
                      {else}
                        <li><a title="{$v.title|escape}" class="action-item crm-hover-button no-popup {$status}"
                            href="{crmURL p="civicrm/event/manage/settings" q="reset=1&action=update&id=`$id`&selectedChild=`$k`"}">{$v.title}</a></li>
                      {/if}
                    {/foreach}
                  </ul>
                </span>
            </div>
          {/if}
        </td>
      {/if}
    </tr>
    {/foreach}

    </tbody>
    </table>
    {if $eventSummary.total_events GT 10}
     <div><a href="{crmURL p='civicrm/admin/event' q='reset=1'}"><i class="crm-i fa-chevron-right" aria-hidden="true"></i> {ts}Browse more events{/ts}...</a></div>
    {/if}
{else}
    <br />
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {ts}There are no active Events to display.{/ts}
      {ts 1=$newEventURL}You can <a href="%1">Create a New Event</a> now.{/ts}
    </div>
{/if}

{if $pager->_totalItems}
    <br/>
    <h3>{ts}Recent Registrations{/ts}</h3>
    <div class="form-item">
        {include file="CRM/Event/Form/Selector.tpl" context="event_dashboard"}
    </div>
{/if}
