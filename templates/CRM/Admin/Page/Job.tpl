{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}

{if $action eq 1 or $action eq 2 or $action eq 8 or $action eq 4}
   {include file="CRM/Admin/Form/Job.tpl"}
{else}
  {capture assign=docUrlText}{ts}(How to setup cron on the command line...){/ts}{/capture}
  {capture assign=runAllURL}{crmURL p='civicrm/admin/runjobs' q="reset=1"}{/capture}
  <div class="help">
    {ts}CiviCRM relies on a number of scheduled jobs that run automatically on a regular basis. These jobs keep data up-to-date and perform other important tasks.{/ts}
    {ts 1=$runAllURL}For most sites, your system administrator should set up one or more 'cron' tasks to run the enabled jobs. You can also <a href="%1">run all scheduled jobs manually</a>, or run specific jobs from this screen.{/ts} {docURL page="sysadmin/setup/jobs" text=$docUrlText}
  </div>
  <div class="crm-content-block crm-block">
  {if $rows}
      {if $action ne 1 and $action ne 2}
        <div class="action-link">
          {crmButton p='civicrm/admin/job/add' q="action=add&reset=1" id="newJob"  icon="plus-circle"}{ts}Add New Scheduled Job{/ts}{/crmButton}
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
                {if array_key_exists('description', $row)}{$row.description}{/if}<br />
                {ts}API Entity:{/ts} {$row.api_entity}<br/>
                {ts}API Action:{/ts} <strong>{$row.api_action}</strong><br/>
            </td>
            <td class="crm-job-name">{if $row.parameters eq null}<em>{ts}no parameters{/ts}</em>{else}{$row.parameters|nl2br}{/if}</td>
            <td class="crm-job-name">{if $row.last_run eq null}never{else}{$row.last_run|crmDate:$config->dateformatDatetime}{/if}</td>
            <td id="row_{$row.id}_status" class="crm-job-is_active">{if $row.is_active eq 1} {ts}Yes{/ts} {else} {ts}No{/ts} {/if}</td>
          <td>{$row.action|smarty:nodefaults|replace:'xx':$row.id}</td>
        </tr>
        {/foreach}
        </table>
    {/strip}

    {if $action ne 1 and $action ne 2}
        <div class="action-link">
          {crmButton p='civicrm/admin/job/add' q="action=add&reset=1" id="newJob-bottom"  icon="plus-circle"}{ts}Add New Scheduled Job{/ts}{/crmButton}
          {crmButton p='civicrm/admin/joblog' q="reset=1" id="jobLog-bottom" icon="list-alt"}{ts}View Log (all jobs){/ts}{/crmButton}
        </div>
    {/if}
</div>
{elseif $action ne 1}
    <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
        {ts}There are no jobs configured.{/ts}
     </div>
     <div class="action-link">
       <a href="{crmURL p='civicrm/admin/job/add' q="action=add&reset=1"}" id="newJob-nojobs" class="button"><span><i class="crm-i fa-plus-circle" aria-hidden="true"></i> {ts}Add New Scheduled Job{/ts}</span></a>
     </div>

{/if}
</div>
{/if}
