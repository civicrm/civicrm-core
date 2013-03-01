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
{if $action eq 1 or $action eq 2 or $action eq 8}
   {include file="CRM/Admin/Form/EventTemplate.tpl"}
{/if}

<div class="crm-content-block">
{if $action ne 1 and $action ne 2}
      <div class="action-link">
        <a href="{crmURL p="civicrm/event/add" q="action=add&is_template=1&reset=1"}" id="newEventTemplate" class="button">
        <span><div class="icon add-icon"></div>{ts}Add Event Template{/ts}</span></a>
        <div class="clear"></div>
      </div>
{/if}
{if $rows}

{include file="CRM/common/jsortable.tpl"}
    {strip}
      <table id="options" class="display">
        <thead>
        <tr>
            <th id="sortable">{ts}Title{/ts}</th>
            <th>{ts}Event Type{/ts}</th>
            <th>{ts}Participant Role{/ts}</th>
            <th>{ts}Participant Listing{/ts}</th>
            <th>{ts}Public Event{/ts}</th>
            <th>{ts}Paid Event{/ts}</th>
            <th>{ts}Allow Online Registration{/ts}</th>
          <th>{ts}Is Active?{/ts}</th>
            <th></th>
        </tr>
        </thead>
        {foreach from=$rows item=row}
          <tr id='rowid{$row.id}' class="{cycle values="odd-row,even-row"} crm-event crm-event_{$row.id}">
              <td class="crm-event-template_title">{$row.template_title}</td>
              <td class="crm-event-event_type">{$row.event_type}</td>
              <td class="crm-event-participant_role">{$row.participant_role}</td>
              <td class="crm-event-participant_listing">{$row.participant_listing}</td>
              <td class="crm-event-is_public">{if $row.is_public eq 1}{ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td class="crm-event-is_monetary">{if $row.is_monetary eq 1}{ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td class="crm-event-is_online_registration">{if $row.is_online_registration eq 1}{ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td class="crm-event-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
              <td class="crm-event-action">{$row.action|replace:'xx':$row.id}</td>
          </tr>
        {/foreach}
      </table>
    {/strip}



{else}
    <div class="messages status no-popup">
    <div class="icon inform-icon"></div>
    {capture assign=crmURL}{crmURL p='civicrm/event/add' q="action=add&is_template=1&reset=1"}{/capture}
        {ts 1=$crmURL}There are no Event Templates present. You can <a href='%1'>add one</a>.{/ts}
    </div>
{/if}
</div>