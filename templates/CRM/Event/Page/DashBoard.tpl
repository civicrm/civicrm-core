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
{* CiviEvent DashBoard (launch page) *}
{capture assign=newEventURL}{crmURL p="civicrm/event/add" q="action=add&reset=1"}{/capture}
{capture assign=configPagesURL}{crmURL p="civicrm/event/manage" q="reset=1"}{/capture}
{capture assign=icalFile}{crmURL p='civicrm/event/ical' q="reset=1" fe=1 a=1}{/capture}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&list=1" fe=1 a=1}{/capture}
{capture assign=rssFeed}{crmURL p='civicrm/event/ical' q="reset=1&list=1&rss=1" fe=1 a=1}{/capture}
{capture assign=htmlFeed}{crmURL p='civicrm/event/ical' q="reset=1&list=1&html=1" fe=1 a=1}{/capture}

{if $eventSummary.total_events}
    <div class="float-right">
  <table class="form-layout-compressed">
     <tr>
    <td><a href="{$configPagesURL}" class="button"><span>{ts}Manage Events{/ts}</span></a></td>
    <td><a href="{$newEventURL}" class="button"><span><div class="icon add-icon"></div>{ts}New Event{/ts}</span></a></td>
     </tr>
  </table>
    </div>
    <h3>{ts}Event Summary{/ts}  {help id="id-event-intro"}&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="{$htmlFeed}"  target="_blank" title="{ts}HTML listing of current and future public events.{/ts}"><img src="{$config->resourceBase}i/applications-internet.png" alt="{ts}HTML listing of current and future public events.{/ts}" /></a>&nbsp;&nbsp;<a href="{$rssFeed}"  target="_blank" title="{ts}Get RSS 2.0 feed for current and future public events.{/ts}"><img src="{$config->resourceBase}i/feed-icon.png" alt="{ts}Get RSS 2.0 feed for current and future public events.{/ts}" /></a>&nbsp;&nbsp;<a href="{$icalFile}" title="{ts}Download iCalendar file for current and future public events.{/ts}"><img src="{$config->resourceBase}i/office-calendar.png" alt="{ts}Download iCalendar file for current and future public events.{/ts}" /></a>&nbsp;&nbsp;<a href="{$icalFeed}"  target="_blank" title="{ts}Get iCalendar feed for current and future public events.{/ts}"><img src="{$config->resourceBase}i/ical_feed.gif" alt="{ts}Get iCalendar feed for current and future public events.{/ts}" /></a></h3>
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
        <td class="crm-event-eventTitle"><a href="{crmURL p="civicrm/event/info" q="reset=1&id=`$id`"}" title="{ts}View event info page{/ts}">{$values.eventTitle}</a></td>
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
                        <a href="{$status.url}" title="{ts 1=$status.name}List %1 participants{/ts}">{$status.name}: {$status.count}</a>
                    {/foreach}
                    <hr />
                {/if}
            {/foreach}
            {if $values.maxParticipants}{ts 1=$values.maxParticipants}(max %1){/ts}{/if}
        </td>
  {if $actionColumn}
        <td class="crm-event-isMap">
            {if $values.isMap}
                <a href="{$values.isMap}" title="{ts}Map event location{/ts}">&raquo;&nbsp;{ts}Map{/ts}</a>&nbsp;|&nbsp;
            {/if}
            {if $values.configure}
              <div class="crm-configure-actions">
                <span id="{$id}" class="btn-slide">{ts}Configure{/ts}
                <ul class="panel" id="panel_info_{$id}">
                <li><a title="Info and Settings" class="action-item-wrap" href="{crmURL p='civicrm/event/manage/settings' q="reset=1&action=update&id=`$id`"}">{ts}Info and Settings{/ts}</a></li>
                <li><a title="Location" class="action-item-wrap {if NOT $values.is_show_location} disabled{/if}" href="{crmURL p='civicrm/event/manage/location' q="reset=1&action=update&id=`$id`"}">{ts}Location{/ts}</a></li>
                <li><a title="Fees" class="action-item {if NOT $values.is_monetary} disabled{/if}" href="{crmURL p='civicrm/event/manage/fee' q="reset=1&action=update&id=`$id`"}">{ts}Fees{/ts}</a></li>
                <li><a title="Online Registration" class="action-item-wrap {if NOT $values.is_online_registration} disabled{/if}" href="{crmURL p='civicrm/event/manage/registration' q="reset=1&action=update&id=`$id`"}">{ts}Online Registration{/ts}</a></li>
          <li><a title="Schedule Reminders" class="action-item-wrap {if NOT $values.reminder} disabled{/if}" href="{crmURL p='civicrm/event/manage/reminder' q="reset=1&action=update&id=`$id`"}">{ts}Schedule Reminders{/ts}</a></li>
                <li><a title="Conference Slots" class="action-item-wrap {if NOT $values.is_subevent} disabled{/if}" href="{crmURL p='civicrm/event/manage/conference' q="reset=1&action=update&id=`$id`"}">{ts}Conference Slots{/ts}</a></li>
                <li><a title="Tell a Friend" class="action-item-wrap {if NOT $values.friend} disabled{/if}" href="{crmURL p='civicrm/event/manage/friend' q="reset=1&action=update&id=`$id`"}">{ts}Tell a Friend{/ts}</a></li>
          <li><a title="{ts}Personal Campaign Pages{/ts}" class="action-item-wrap {if NOT $values.is_pcp_enabled} disabled{/if}" href="{crmURL p='civicrm/event/manage/pcp' q="reset=1&action=update&id=`$id`"}">{ts}Personal Campaign Pages{/ts}</a></li>
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
     <div><a href="{crmURL p='civicrm/admin/event' q='reset=1'}">&raquo; {ts}Browse more events{/ts}...</a></div>
    {/if}
{else}
    <br />
    <div class="messages status no-popup">
        <table>
            <tr><div class="icon inform-icon"></div></tr>
            <tr>
                {ts}There are no active Events to display.{/ts}
                {ts 1=$newEventURL}You can <a href="%1">Create a New Event</a> now.{/ts}
            </tr>
        </table>
    </div>
{/if}

{if $pager->_totalItems}
    <br/>
    <h3>{ts}Recent Registrations{/ts}</h3>
    <div class="form-item">
        {include file="CRM/Event/Form/Selector.tpl" context="event_dashboard"}
    </div>
{/if}
