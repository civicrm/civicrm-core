{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{capture assign=runAllURL}{crmURL p='civicrm/admin/runjobs' q="reset=1"}{/capture}
<div class="help">
    {ts 1=$runAllURL}You can configure scheduled jobs (cron tasks) for your CiviCRM installation. For most sites, your system administrator should set up one or more 'cron' tasks to run the enabled jobs. However, you can also <a href="%1">run all scheduled jobs manually</a>, or run specific jobs from this screen (click 'more' and then 'Execute Now').{/ts} {docURL page="sysadmin/setup/jobs" text="(Job parameters and command line syntax documentation...)"}
</div>

{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 4}
   {include file="CRM/Admin/Form/Job.tpl"}
{else}

<div class="crm-content-block crm-block">
{if $rows}

      {if $action ne 1 and $action ne 2}
        <div class="action-link">
          {crmButton q="action=add&reset=1" id="newJob"  icon="plus-circle"}{ts}Add New Scheduled Job{/ts}{/crmButton}
          {crmButton p='civicrm/admin/joblog' q="reset=1" id="jobLog" icon="list-alt"}{ts}View Log (all jobs){/ts}{/crmButton}
        </div>
      {/if}

<div id="ltype">
    {strip}
        {* handle enable/disable actions*}
       {include file="CRM/common/enableDisableApi.tpl"}
        <table class="selector row-highlight">
        <tr class="columnheader">
            <th >{ts}Name (Frequency)/Description{/ts}</th>
            <th >{ts}Command/Parameters{/ts}</th>
            <th >{ts}Last Run{/ts}</th>
            <th >{ts}Enabled?{/ts}</th>
            <th ></th>
        </tr>
        {foreach from=$rows item=row}
        <tr id="job-{$row.id}" class="crm-entity {cycle values="odd-row,even-row"} {$row.class}{if NOT $row.is_active} disabled{/if}">
            <td class="crm-job-name"><strong><span data-field="name">{$row.name}</span></strong> ({$row.run_frequency})<br/>
                {$row.description}<br />
                {ts}API Entity:{/ts} {$row.api_entity}<br/>
                {ts}API Action:{/ts} <strong>{$row.api_action}</strong><br/>
            </td>
            <td class="crm-job-name">{if $row.parameters eq null}<em>{ts}no parameters{/ts}</em>{else}<pre>{$row.parameters}</pre>{/if}</td>
            <td class="crm-job-name">{if $row.last_run eq null}never{else}{$row.last_run|crmDate:$config->dateformatDatetime}{/if}</td>
            <td id="row_{$row.id}_status" class="crm-job-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
        <div class="action-link">
          {crmButton q="action=add&reset=1" id="newJob-bottom"  icon="plus-circle"}{ts}Add New Scheduled Job{/ts}{/crmButton}
          {crmButton p='civicrm/admin/joblog' q="reset=1" id="jobLog-bottom" icon="list-alt"}{ts}View Log (all jobs){/ts}{/crmButton}
        </div>
    {/if}
</div>
{elseif $action ne 1}
    <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts}There are no jobs configured.{/ts}
     </div>
     <div class="action-link">
       <a href="{crmURL p='civicrm/admin/job' q="action=add&reset=1"}" id="newJob-nojobs" class="button"><span><i class="crm-i fa-plus-circle"></i> {ts}Add New Scheduled Job{/ts}</span></a>
     </div>

{/if}
</div>
{/if}
