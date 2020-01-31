{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
<div class="help">
    {ts}This screen presents the list of most recent 1,000 scheduled jobs log entries.{/ts} {$docLink}
</div>

<div class="crm-content-block crm-block">

{if $jobId}
    <h3>{ts}List of log entries for:{/ts} {$jobName}</h3>
{/if}

  <div class="action-link">
    <a href="{crmURL p='civicrm/admin/job' q="reset=1"}" id="jobsList-top" class="button"><span><i class="crm-i fa-chevron-left"></i> {ts}Back to Scheduled Jobs Listing{/ts}</span></a>
  </div>

{if $rows}
  <div id="ltype">
        {strip}
        {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
        <table class="selector row-highlight">
        <tr class="columnheader">
            <th >{ts}Date{/ts}</th>
            <th >{ts}Job Name{/ts}</th>
            <th >{ts}Command{/ts}/{ts}Job Status{/ts}/{ts}Additional Information{/ts}</th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="job-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}">
            <td class="crm-joblog-run_datetime">{$row.run_time}</td>
            <td class="crm-joblog-name">{$row.name}</td>
            <td class="crm-joblog-details">
                <div class="crm-joblog-command">{$row.command}</div>
                {if $row.description}<div class="crm-joblog-description"><span class="bold">Summary</span><br/>{$row.description}</div>{/if}
              {if $row.data}<div class="crm-joblog-data" style="border-top:1px solid #ccc; margin-top: 10px;"><span class="bold">Details</span><br/><pre>{$row.data}</pre></div>{/if}
            </td>
        </tr>
        {/foreach}
        </table>
        {/strip}

  </div>
{elseif $action ne 1}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>&nbsp;
      {if $jobId}
        {ts}This scheduled job does not have any log entries.{/ts}
      {else}
        {ts}There are no scheduled job log entries.{/ts}
      {/if}
     </div>
{/if}

  <div class="action-link">
    <a href="{crmURL p='civicrm/admin/job' q="reset=1"}" id="jobsList-bottom" class="button"><span><i class="crm-i fa-chevron-left"></i> {ts}Back to Scheduled Jobs Listing{/ts}</span></a>
  </div>
</div>
