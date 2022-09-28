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
    {ts}This screen presents the list of most recent 1,000 scheduled jobs log entries.{/ts} {if !empty($docLink)}{$docLink}{/if}
</div>

<div class="crm-content-block crm-block">

{if $jobId}
    <h3>{ts}List of log entries for:{/ts} {$jobName}</h3>
{/if}

  <div class="action-link">
    <a href="{crmURL p='civicrm/admin/job' q="reset=1"}" id="jobsList-top" class="button"><span><i class="crm-i fa-chevron-left" aria-hidden="true"></i> {ts}Back to Scheduled Jobs Listing{/ts}</span></a>
    {if $jobRunUrl}
      <a href="{$jobRunUrl}" id="jobsList-run-top" class="button"><span><i class="crm-i fa-play" aria-hidden="true"></i> {ts}Execute Now{/ts}</span></a>
    {/if}
  </div>

{if !empty($rows)}
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
        <tr id="job-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}">
            <td class="crm-joblog-run_datetime">{$row.run_time}</td>
            <td class="crm-joblog-name">{$row.name}</td>
            <td class="crm-joblog-details">
                <div class="crm-joblog-command">{$row.command}</div>
                {if $row.description}<div class="crm-joblog-description"><span class="bold">{ts}Summary{/ts}</span><br/>{$row.description}</div>{/if}
                {if $row.data}<div class="crm-joblog-data" style="border-top:1px solid #ccc; margin-top: 10px;"><span class="bold">{ts}Details{/ts}</span><br/><pre>{$row.data}</pre></div>{/if}
            </td>
        </tr>
        {/foreach}
        </table>
        {/strip}

  </div>
{elseif $action ne 1}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
      {if $jobId}
        {ts}This scheduled job does not have any log entries.{/ts}
      {else}
        {ts}There are no scheduled job log entries.{/ts}
      {/if}
     </div>
{/if}

  <div class="action-link">
    <a href="{crmURL p='civicrm/admin/job' q="reset=1"}" id="jobsList-bottom" class="button"><span><i class="crm-i fa-chevron-left" aria-hidden="true"></i> {ts}Back to Scheduled Jobs Listing{/ts}</span></a>
    {if $jobRunUrl}
      <a href="{$jobRunUrl}" id="jobsList-run-bottom" class="button"><span><i class="crm-i fa-play" aria-hidden="true"></i> {ts}Execute Now{/ts}</span></a>
    {/if}
  </div>
</div>
