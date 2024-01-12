{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="crm-event-userdashboard-pre"}
{/crmRegion}
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
                    <tr id='rowid{$row.participant_id}' class=" crm-event-participant-id_{$row.participant_id} {cycle values="odd-row,even-row"}{if $row.status eq 'Cancelled'} disabled{/if}">
                       <td class="crm-participant-event-id_{$row.event_id}"><a href="{crmURL p='civicrm/event/info' q="reset=1&id=`$row.event_id`&context=dashboard"}">{$row.event_title}</a></td>
                       <td class="crm-participant-event_start_date">
                            {$row.event_start_date|crmDate}
                            {if $row.event_end_date}
                                &nbsp; - &nbsp;
                                {* Only show end time if end date = start date *}
                                {if $row.event_end_date|crmDate:"%Y%m%d" == $row.event_start_date|crmDate:"%Y%m%d"}
                                    {$row.event_end_date|crmDate:0:1}
                                {else}
                                    {$row.event_end_date|crmDate}
                                {/if}
                            {/if}
                       </td>
                       <td class="crm-participant-participant_status">{$row.participant_status}</td>
                    </tr>
                {/foreach}
            </table>
        {/strip}
    {else}
        <div class="messages status no-popup">
           {icon icon="fa-info-circle"}{/icon}
           {ts}You are not registered for any current or upcoming Events.{/ts}
        </div>
    {/if}
</div>
{crmRegion name="crm-event-userdashboard-post"}
{/crmRegion}
