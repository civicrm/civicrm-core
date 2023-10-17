{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* Edit/Run Scheduled Jobs *}
<div class="crm-block crm-form-block crm-job-form-block">

{if $action eq 8}
  <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
        {ts}WARNING: Deleting this Scheduled Job will cause some important site functionality to stop working.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{elseif $action eq 4}
  <div class="messages status no-popup">
      {icon icon="fa-info-circle"}{/icon}
        {ts 1=$jobName|escape:html}Are you sure you would like to execute %1 job?{/ts}
  </div>
{else}
  <div class="help">
    {capture assign=docUrlText}{ts}Job parameters and command line syntax documentation{/ts}{/capture}
    {docURL page="user/initial-set-up/scheduled-jobs" text=$docUrlText}
  </div>
  <table class="form-layout-compressed">
    <tr class="crm-job-form-block-name">
        <td class="label">{$form.name.label}</td><td>{$form.name.html}</td>
    </tr>
    <tr class="crm-job-form-block-description">
        <td class="label">{$form.description.label}</td><td>{$form.description.html}</td>
    </tr>
    <tr class="crm-job-form-block-run_frequency">
        <td class="label">{$form.run_frequency.label}</td><td>{$form.run_frequency.html}</td>
    </tr>
    <tr class="crm-job-form-block-api_entity">
      <td class="label">{$form.api_entity.label}</td><td>{$form.api_entity.html}</td>
    </tr>
    <tr class="crm-job-form-block-api_action">
      <td class="label">{$form.api_action.label}</td><td>{$form.api_action.html}</td>
    </tr>
    <tr class="crm-job-form-block-parameters">
      <td class="label">{$form.parameters.label}<br />{docURL page="user/initial-set-up/scheduled-jobs/#parameters"}</td>
      <td>{$form.parameters.html}</td>
    </tr>
    <tr class="crm-job-form-block-scheduled-run-date">
        <td class="label">{$form.scheduled_run_date.label}</td>
        <td>{$form.scheduled_run_date.html}<br />
            <div class="description">{ts}Do not run this job before this date / time. The run frequency selected above will apply thereafter.{/ts}<br />
              {if $action eq 1}{ts}Leave blank to run as soon as possible.{/ts}{else}{ts}Leave blank to run at next run frequency.{/ts}{/if}
            </div>
        </td>
    </tr>
    <tr class="crm-job-form-block-is_active">
      <td></td><td>{$form.is_active.html}&nbsp;{$form.is_active.label}</td>
    </tr>
  </table>
{/if}
  <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="bottom"}</div>
</div>

