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
    <h2>{ts}List of log entries for:{/ts} {$jobName}</h2>
{/if}

  <div class="action-link">
    <a href="{crmURL p='civicrm/admin/job' q="reset=1"}" id="jobsList-top" class="button"><span><i class="crm-i fa-chevron-left" role="img" aria-hidden="true"></i> {ts}Back to Scheduled Jobs Listing{/ts}</span></a>
    {if $jobRunUrl}
      <a href="{$jobRunUrl}" id="jobsList-run-top" class="button"><span><i class="crm-i fa-play" role="img" aria-hidden="true"></i> {ts}Execute Now{/ts}</span></a>
    {/if}
  </div>

{if !empty($rows)}
  <div id="ltype">
        {strip}
        {* handle enable/disable actions*}
   {include file="CRM/common/enableDisableApi.tpl"}
        <table class="selector row-highlight vertical-align-baseline">
        <tr class="columnheader">
            <th >{ts}Date{/ts}</th>
            {if !$jobId}<th >{ts}Job Name{/ts}</th>{/if}
            <th >{ts}Command{/ts}/{ts}Job Status{/ts}/{ts}Additional Information{/ts}</th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="job-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"}{if !empty($row.class)} {$row.class}{/if}">
            <td class="crm-joblog-run_datetime">{$row.run_time}</td>
            {if !$jobId}<td class="crm-joblog-name">{$row.name}</td>{/if}
            <td class="crm-joblog-details">
                {if $row.description}
                  <!-- TODO:
                    It would be nice to apply some CSS based on the messageType (success|error|info)
                    We also have $row.logLevel (debug|info|error|...)
                  -->
                  <p class="crm-joblog-description {$row.statusClass}">
                    <strong>{$row.description}</strong>
                  </p>
                  {if $row.resultValues}
                    {if $row.resultIsMultiline}
                    <details class="crm-joblog-data crm-accordion-light">
                      <summary>{ts}Result{/ts}</summary>
                      <div class="crm-accordion-body">
                        <pre>
                          {$row.resultValues|escape}
                        </pre>
                      </div>
                    </details>
                    {else}
                      <p>{ts}Result:{/ts} <code>{$row.resultValues|escape}</code></p>
                    {/if}
                  {/if}
                {/if}
                {if $row.command}
                <details class="crm-joblog-data crm-accordion-light">
                  <summary class="crm-joblog-command">{ts}Api call details for{/ts} <code>{$row.command}</code></summary>
                  <div class="crm-accordion-body">
                    {if $row.data}
                      <pre>{$row.data|trim}</pre>
                    {/if}
                  </div>
                </details>
                {/if}
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
    <a href="{crmURL p='civicrm/admin/job' q="reset=1"}" id="jobsList-bottom" class="button"><span><i class="crm-i fa-chevron-left" role="img" aria-hidden="true"></i> {ts}Back to Scheduled Jobs Listing{/ts}</span></a>
    {if $jobRunUrl}
      <a href="{$jobRunUrl}" id="jobsList-run-bottom" class="button"><span><i class="crm-i fa-play" role="img" aria-hidden="true"></i> {ts}Execute Now{/ts}</span></a>
    {/if}
  </div>
</div>
