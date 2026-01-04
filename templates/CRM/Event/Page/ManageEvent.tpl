{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=newEventURL}{crmURL p='civicrm/event/add' q="action=add&reset=1"}{/capture}

<div class="crm-block crm-content-block">
<div class="float-right">
  {include file="CRM/Event/Page/iCalLinks.tpl"}
</div>

<div class="action-link">
  <a accesskey="N" href="{$newEventURL}" id="newManageEvent" class="button crm-popup">
    <span><i class="crm-i fa-calendar-plus-o" role="img" aria-hidden="true"></i> {ts}Add Event{/ts}</span>
  </a>
  <div class="clear"></div>
</div>

{include file="CRM/Event/Form/SearchEvent.tpl"}

{if $rows}
<div id="event_status_id" class="crm-block crm-manage-events">
  {strip}
  {include file="CRM/common/pager.tpl" location="top"}
  {* handle enable/disable actions*}
  {include file="CRM/common/enableDisableApi.tpl"}
  {include file="CRM/common/jsortable.tpl"}
    <table id="options" class="display">
      <thead>
      <tr>
        <th>{ts}Event{/ts}</th>
        <th>{ts}City{/ts}</th>
        <th>{ts}State/Province{/ts}</th>
        <th>{ts}Type{/ts}</th>
        <th>{ts}Public?{/ts}</th>
        <th>{ts}Starts{/ts}</th>
        <th>{ts}Ends{/ts}</th>
        {if call_user_func(array('CRM_Campaign_BAO_Campaign','isComponentEnabled'))}
          <th>{ts}Campaign{/ts}</th>
        {/if}
        <th>{ts}Active?{/ts}</th>
        <th></th>
        <th class="hiddenElement"></th>
        <th class="hiddenElement"></th>
      </tr>
      </thead>
      {foreach from=$rows key=keys item=row}
        {if $keys neq 'tab'}
          <tr id="event-{$row.id}" class="crm-entity {if NOT $row.is_active} disabled{/if}">
          <td class="crm-event_{$row.id}">
            <a href="{crmURL p='civicrm/event/info' q="id=`$row.id`&reset=1"}"
               title="{ts escape='htmlattribute'}View event info page{/ts}" class="bold">{$row.title|smarty:nodefaults|purify}</a>&nbsp;&nbsp;({ts}ID:{/ts} {$row.id})<br/>
               <span><b>{$row.repeat}</b></span>
          </td>
          <td class="crm-event-city">{$row.city}</td>
          <td class="crm-event-state_province">{$row.state_province}</td>
          <td class="crm-event-event_type">{$row.event_type}</td>
          <td class="crm-event-is_public">{if $row.is_public eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td class="crm-event-start_date" data-order="{$row.start_date|crmDate:'%Y-%m-%d'}">{$row.start_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
          <td class="crm-event-end_date" data-order="{$row.end_date|crmDate:'%Y-%m-%d'}">{$row.end_date|crmDate:"%b %d, %Y %l:%M %P"}</td>
          {if call_user_func(array('CRM_Campaign_BAO_Campaign','isComponentEnabled'))}
            <td class="crm-event-campaign">{$row.campaign}</td>
          {/if}
          <td class="crm-event_status" id="row_{$row.id}_status">
            {if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}
          </td>
          <td class="crm-event-actions right nowrap">
            <div class="crm-configure-actions">
              <span id="event-configure-{$row.id}" class="btn-slide crm-hover-button">{ts}Configure{/ts}
                <ul class="panel" id="panel_info_{$row.id}">
                  {foreach from=$rows.tab key=k item=v}
                    {assign var="fld" value=$v.field}
                    {if NOT $row.$fld}{assign var="status" value="disabled"}{else}{assign var="status" value="enabled"}{/if}
                      {if $k eq 'reminder'}
                        <li><a title="{$v.title|escape:'html'}" class="action-item crm-hover-button {$status}"
                           href="{crmURL p="`$v.url`" q="reset=1&action=browse&setTab=1&id=`$row.id`"}">{$v.title}</a>
                      {else}
                        <li><a title="{$v.title|escape:'html'}" class="action-item crm-hover-button {$status}"
                           href="{crmURL p="`$v.url`" q="reset=1&action=update&id=`$row.id`"}">{$v.title}</a></li>
                      {/if}
                  {/foreach}
                </ul>
              </span>
            </div>

            <div class=crm-event-participants>
              <span id="event-participants-{$row.id}" class="btn-slide crm-hover-button">{ts}Participants{/ts}
                <ul class="panel" id="panel_participants_{$row.id}">
                  {if $findParticipants.statusCounted}
                    <li>
                      <a title="{ts escape='htmlattribute'}Counted Participants{/ts}" class="action-item crm-hover-button" href="{crmURL p='civicrm/event/search'
                      q="reset=1&force=1&status=true&event=`$row.id`"}">{$findParticipants.statusCounted}
                      </a>
                    </li>
                  {/if}
                  {if $findParticipants.statusNotCounted}
                    <li>
                      <a title="{ts escape='htmlattribute'}Participants Not Counted{/ts}" class="action-item crm-hover-button"
                           href="{crmURL p='civicrm/event/search'
                           q="reset=1&force=1&status=false&event=`$row.id`"}">{$findParticipants.statusNotCounted}
                      </a>
                    </li>
                  {/if}
                  {if $row.participant_listing_id}
                    <li>
                      <a title="{ts escape='htmlattribute'}Public Participant Listing{/ts}" class="action-item crm-hover-button"
                         href="{crmURL p='civicrm/event/participant' q="reset=1&id=`$row.id`"
                         fe='true'}">{ts}Public Participant Listing{/ts}
                      </a>
                    </li>
                  {/if}
                </ul>
              </span>
            </div>
            <div class="crm-event-links">
              {$row.eventlinks|replace:'xx':$row.id nofilter}
            </div>
            <div class="crm-event-more">
              {$row.action|replace:'xx':$row.id nofilter}
            </div>
          </td>
          <td class="crm-event-start_date hiddenElement">{$row.start_date|crmDate}</td>
          <td class="crm-event-end_date hiddenElement">{$row.end_date|crmDate}</td>
        </tr>
        {/if}
      {/foreach}
    </table>
  {include file="CRM/common/pagerAToZ.tpl"}
  {include file="CRM/common/pager.tpl" location="bottom"}
  {/strip}
</div>
{else}
  {if $isSearch eq 1}
  <div class="status messages">
    {icon icon="fa-info-circle"}{/icon}
    {capture assign=browseURL}{crmURL p='civicrm/event/manage' q="reset=1"}{/capture}
    {ts}No available Events match your search criteria. Suggestions:{/ts}
    <div class="spacer"></div>
    <ul>
      <li>{ts}Check your spelling.{/ts}</li>
      <li>{ts}Try "Search All or by Date Range".{/ts}</li>
      <li>{ts}Try a different spelling or use fewer letters.{/ts}</li>
      <li>{ts}Make sure you have enough privileges in the access control system.{/ts}</li>
    </ul>
    {ts 1=$browseURL}Or you can <a href='%1'>browse all available Current Events</a>.{/ts}
  </div>
    {else}
  <div class="messages status no-popup">
    {icon icon="fa-info-circle"}{/icon}
    {ts 1=$newEventURL}There are no events scheduled for the date range. You can <a href='%1'>add one</a>.{/ts}
  </div>
  {/if}
{/if}
</div>
