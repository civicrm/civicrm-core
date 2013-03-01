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
{capture assign=newEventURL}{crmURL p='civicrm/event/add' q="action=add&reset=1"}{/capture}
{capture assign=icalFile}{crmURL p='civicrm/event/ical' q="reset=1" fe=1}{/capture}
{capture assign=icalFeed}{crmURL p='civicrm/event/ical' q="reset=1&list=1" fe=1}{/capture}
{capture assign=rssFeed}{crmURL p='civicrm/event/ical' q="reset=1&list=1&rss=1" fe=1}{/capture}
{capture assign=htmlFeed}{crmURL p='civicrm/event/ical' q="reset=1&list=1&html=1" fe=1}{/capture}
<div class="float-right">
  <a href="{$htmlFeed}" target="_blank" title="{ts}HTML listing of current and future public events.{/ts}">
    <img src="{$config->resourceBase}i/applications-internet.png"
         alt="{ts}HTML listing of current and future public events.{/ts}" />
  </a>&nbsp;&nbsp;
  <a href="{$rssFeed}" target="_blank" title="{ts}Get RSS 2.0 feed for current and future public events.{/ts}">
    <img src="{$config->resourceBase}i/feed-icon.png"
         alt="{ts}Get RSS 2.0 feed for current and future public events.{/ts}" />
  </a>&nbsp;&nbsp;
  <a href="{$icalFile}" title="{ts}Download iCalendar file for current and future public events.{/ts}">
    <img src="{$config->resourceBase}i/office-calendar.png"
         alt="{ts}Download iCalendar file for current and future public events.{/ts}" />
  </a>&nbsp;&nbsp;
  <a href="{$icalFeed}" target="_blank" title="{ts}Get iCalendar feed for current and future public events.{/ts}">
    <img src="{$config->resourceBase}i/ical_feed.gif"
         alt="{ts}Get iCalendar feed for current and future public events.{/ts}" />
  </a>&nbsp;&nbsp;&nbsp;{help id='icalendar'}
</div>
{include file="CRM/Event/Form/SearchEvent.tpl"}

<div class="action-link">
  <a accesskey="N" href="{$newEventURL}" id="newManageEvent" class="button">
    <span><div class="icon add-icon"></div>{ts}Add Event{/ts}</span>
  </a>
  <div class="clear"></div>
</div>
{if $rows}
<div id="event_status_id" class="crm-block crm-manage-events">
  {strip}
  {include file="CRM/common/pager.tpl" location="top"}
  {include file="CRM/common/pagerAToZ.tpl"}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisable.tpl"}
  {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
      <thead>
      <tr>
        <th>{ts}Event{/ts}</th>
        <th>{ts}City{/ts}</th>
        <th>{ts}State/Province{/ts}</th>
        <th>{ts}Public?{/ts}</th>
        <th>{ts}Starts{/ts}</th>
        <th>{ts}Ends{/ts}</th>
        {if call_user_func(array('CRM_Campaign_BAO_Campaign','isCampaignEnable'))}
          <th>{ts}Campaign{/ts}</th>
        {/if}
        <th>{ts}Active?{/ts}</th>
        <th></th>
        <th class="hiddenElement"></th>
        <th class="hiddenElement"></th>
      </tr>
      </thead>
      {foreach from=$rows item=row}
        <tr id="row_{$row.id}" class="{if NOT $row.is_active} disabled{/if}">
          <td class="crm-event_{$row.id}">
            <a href="{crmURL p='civicrm/event/info' q="id=`$row.id`&reset=1"}"
               title="{ts}View event info page{/ts}" class="bold">{$row.title}</a>&nbsp;&nbsp;({ts}ID:{/ts} {$row.id})
          </td>
          <td class="crm-event-city">{$row.city}</td>
          <td class="crm-event-state_province">{$row.state_province}</td>
          <td class="crm-event-is_public">{if $row.is_public eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="crm-event-start_date">{$row.start_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
          <td class="crm-event-end_date">{$row.end_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
          {if call_user_func(array('CRM_Campaign_BAO_Campaign','isCampaignEnable'))}
            <td class="crm-event-campaign">{$row.campaign}</td>
          {/if}
          <td class="crm-event_status" id="row_{$row.id}_status">
            {if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}
          </td>
          <td class="crm-event-actions right nowrap">
            <div class="crm-configure-actions">
              <span id="event-configure-{$row.id}" class="btn-slide">{ts}Configure{/ts}
                <ul class="panel" id="panel_info_{$row.id}">
                  <li>
                    <a title="{ts}Info and Settings{/ts}" class="action-item-wrap"
                       href="{crmURL p='civicrm/event/manage/settings'
                       q="reset=1&action=update&id=`$row.id`"}">{ts}Info and Settings{/ts}
                    </a>
                  </li>
                  <li>
                    <a title="{ts}Location{/ts}" class="action-item-wrap {if NOT $row.is_show_location} disabled{/if}"
                       href="{crmURL p='civicrm/event/manage/location'
                       q="reset=1&action=update&id=`$row.id`"}">{ts}Location{/ts}
                    </a>
                  </li>
                  <li>
                    <a title="{ts}Fees{/ts}" class="action-item {if NOT $row.is_monetary} disabled{/if}"
                       href="{crmURL p='civicrm/event/manage/fee' q="reset=1&action=update&id=`$row.id`"}">{ts}Fees{/ts}
                    </a>
                  </li>
                  <li>
                    <a title="{ts}Online Registration{/ts}" class="action-item-wrap
                    {if NOT $row.is_online_registration} disabled{/if}" href="{crmURL
                    p='civicrm/event/manage/registration' q="reset=1&action=update&id=`$row.id`"}">
                      {ts}Online Registration{/ts}
                    </a>
                  </li>
                  <li>
                    <a title="{ts}Schedule Reminders{/ts}" class="action-item-wrap
                    {if NOT $row.reminder} disabled{/if}" href="{crmURL p='civicrm/event/manage/reminder'
                    q="reset=1&action=update&id=`$row.id`"}">{ts}Schedule Reminders{/ts}
                    </a>
                  </li>
                  {if $eventCartEnabled}
                    <li>
                      <a title="{ts}Conference Slots{/ts}" class="action-item-wrap
                      {if NOT $row.slot_label_id} disabled{/if}" href="{crmURL p='civicrm/event/manage/conference'
                      q="reset=1&action=update&id=`$row.id`"}">{ts}Conference Slots{/ts}
                      </a>
                    </li>
                  {/if}
                  <li>
                    <a title="{ts}Tell a Friend{/ts}" class="action-item-wrap {if NOT $row.friend} disabled{/if}"
                       href="{crmURL p='civicrm/event/manage/friend'
                       q="reset=1&action=update&id=`$row.id`"}">{ts}Tell a Friend{/ts}
                    </a>
                  </li>
                  <li>
                    <a title="{ts}Personal Campaign Pages{/ts}" class="action-item-wrap
                    {if NOT $row.is_pcp_enabled} disabled{/if}" href="{crmURL p='civicrm/event/manage/pcp'
                    q="reset=1&action=update&id=`$row.id`"}">{ts}Personal Campaign Pages{/ts}
                    </a>
                  </li>
                </ul>
              </span>
            </div>

            <div class=crm-event-participants>
              <span id="event-participants-{$row.id}" class="btn-slide">{ts}Participants{/ts}
                <ul class="panel" id="panel_participants_{$row.id}">
                  {if $findParticipants.statusCounted}
                    <li>
                      <a title="Counted" class="action-item-wrap" href="{crmURL p='civicrm/event/search'
                      q="reset=1&force=1&status=true&event=`$row.id`"}">{$findParticipants.statusCounted}
                      </a>
                    </li>
                  {/if}
                  {if $findParticipants.statusNotCounted}
                    <li>
                      <a title="Not Counted" class="action-item-wrap"
                           href="{crmURL p='civicrm/event/search'
                           q="reset=1&force=1&status=false&event=`$row.id`"}">{$findParticipants.statusNotCounted}
                      </a>
                    </li>
                  {/if}
                  {if $row.participant_listing_id}
                    <li>
                      <a title="Public Participant Listing" class="action-item-wrap"
                         href="{crmURL p='civicrm/event/participant' q="reset=1&id=`$row.id`"
                         fe='true'}">{ts}Public Participant Listing{/ts}
                      </a>
                    </li>
                  {/if}
                </ul>
              </span>
            </div>

            <div class="crm-event-links">
              <span id="event-links-{$row.id}" class="btn-slide">{ts}Event Links{/ts}
                <ul class="panel" id="panel_links_{$row.id}">
                  <li>
                    <a title="Register Participant" class="action-item" href="{crmURL p='civicrm/participant/add'
                    q="reset=1&action=add&context=standalone&eid=`$row.id`"}">{ts}Register Participant{/ts}</a>
                  </li>
                  <li>
                    <a title="Event Info" class="action-item" href="{crmURL p='civicrm/event/info'
                    q="reset=1&id=`$row.id`" fe='true'}" target="_blank">{ts}Event Info{/ts}
                    </a>
                  </li>
                  {if $row.is_online_registration}
                    <li>
                      <a title="Online Registration (Test-drive)" class="action-item"
                         href="{crmURL p='civicrm/event/register'
                         q="reset=1&action=preview&id=`$row.id`"}">{ts}Registration (Test-drive){/ts}
                      </a>
                    </li>
                    <li>
                      <a title="Online Registration (Live)" class="action-item" href="{crmURL p='civicrm/event/register'
                      q="reset=1&id=`$row.id`" fe='true'}" target="_blank">{ts}Registration (Live){/ts}
                      </a>
                    </li>
                  {/if}
                </ul>
              </span>
            </div>
            <div class="crm-event-more">
              {$row.action|replace:'xx':$row.id}
            </div>
          </td>
          <td class="crm-event-start_date hiddenElement">{$row.start_date|crmDate}</td>
          <td class="crm-event-end_date hiddenElement">{$row.end_date|crmDate}</td>
        </tr>
      {/foreach}
    </table>
  {include file="CRM/common/pager.tpl" location="bottom"}
  {/strip}
</div>
{else}
  {if $isSearch eq 1}
  <div class="status messages">
    <div class="icon inform-icon"></div>
    {capture assign=browseURL}{crmURL p='civicrm/event/manage' q="reset=1"}{/capture}
    {ts}No available Events match your search criteria. Suggestions:{/ts}
    <div class="spacer"></div>
    <ul>
      <li>{ts}Check your spelling.{/ts}</li>
      <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
      <li>{ts}Make sure you have enough privileges in the access control system.{/ts}</li>
    </ul>
    {ts 1=$browseURL}Or you can <a href='%1'>browse all available Current Events</a>.{/ts}
  </div>
    {else}
  <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {ts 1=$newEventURL}There are no events scheduled for the date range. You can <a href='%1'>add one</a>.{/ts}
  </div>
  {/if}
{/if}
