{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{crmRegion name="crm-activity-userdashboard-pre"}
{/crmRegion}
<div class="view-content">
    {if $activity_rows}
        {strip}
            <div class="description">
                {ts}Click on the activity subject for more information.{/ts}
            </div>
            <table class="selector">
                <tr class="columnheader">
                    <th>{ts}Type{/ts}</th>
                    <th>{ts}Subject{/ts}</th>
                    <th>{ts}Added by{/ts}</th>
                    <th>{ts}With{/ts}</th>
                    <th>{ts}Date{/ts}</th>
                    <th>{ts}Status{/ts}</th>
                </tr>
                {counter start=0 skip=1 print=false}
                {capture assign="no_subject"}{ts}(no subject){/ts}{/capture}
                {foreach from=$activity_rows item=row}
                    <tr id='rowid{$row.activity_id}' class=" crm-activity-id_{$row.activity_id} {cycle values="odd-row,even-row"}">
                       <td class="crm-activity_type">{$row.activity_type}</td>
                       <td class="crm-activity_subject"><a href="{crmURL p='civicrm/activity'
q="action=view&reset=1&id=`$row.activity_id`&cid=`$row.contact_id`&context=dashboard"}">{$row.activity_subject|default:$no_subject}</a></td>
                       <td class="crm-source_contact_name">
                          <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$row.source_contact_id`"}">{$row.source_contact_name}</a>;
                       </td>
                       <td class="crm-target_contact_name">
                          {foreach from=$row.target_contact_name item=name key=cid}
                              <a href="{crmURL p="civicrm/contact/view" q="reset=1&cid=`$cid`"}">{$name}</a>;
                          {/foreach}
                       </td>
                       <td class="crm-activity_date_time">{$row.activity_date_time|crmDate}</td>
                       <td class="crm-activity_status">{$row.activity_status}</td>
                    </tr>
                {/foreach}
            </table>
        {/strip}
    {else}
        <div class="messages status no-popup">
           {icon icon="fa-info-circle"}{/icon}
                 {ts}There are no scheduled activities assigned to you.{/ts}

        </div>
    {/if}
</div>
{crmRegion name="crm-activity-userdashboard-post"}
{/crmRegion}
