{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.5                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2014                                |
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
<div class="view-content">
    {if $event_rows}
        {strip}
            <div class="description">
                {ts}Click on the event name for more information.{/ts}
            </div>
            <table class="selector">
                <tr class="columnheader">
                    <th>{ts}Event{/ts}</th>
                    <th>{ts}Event Date(s){/ts}</th>
                    <th>{ts}Status{/ts}</th>
                    <th></th>
                </tr>
                {counter start=0 skip=1 print=false}
                {foreach from=$event_rows item=row}
                    <tr id='rowid{$row.participant_id}' class=" crm-event-participant-id_{$row.participant_id} {cycle values="odd-row,even-row"}{if $row.status eq Cancelled} disabled{/if}">
                       <td class="crm-participant-event-id_{$row.event_id}"><a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$row.event_id`&context=dashboard"}">{$row.event_title}</a></td>
                       <td class="crm-participant-event_start_date">
                            {$row.event_start_date|crmDate}
                            {if $row.event_end_date}
                                &nbsp; - &nbsp;
                                {* Only show end time if end date = start date *}
                                {if $row.event_end_date|date_format:"%Y%m%d" == $row.event_start_date|date_format:"%Y%m%d"}
                                    {$row.event_end_date|crmDate:0:1}
                                {else}
                                    {$row.event_end_date|crmDate}
                                {/if}
                            {/if}
                       </td>
                       <td class="crm-participant-participant_status">{$row.participant_status}</td>
                       <td class="crm-participant-showConfirmUrl">
                            {if $row.showConfirmUrl}
                                <a href="{crmURL p='civicrm/event/confirm' q="reset=1&participantId=`$row.participant_id`"}">{ts}Confirm Registration{/ts}</a>                            
                            {/if}
                        </td>
                    </tr>
                {/foreach}
            </table>
        {/strip}
    {else}
        <div class="messages status no-popup">
           <div class="icon inform-icon"></div>&nbsp;
                 {ts}You are not registered for any current or upcoming Events.{/ts}
               
        </div>
    {/if}
</div>
