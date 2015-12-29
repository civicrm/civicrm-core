{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
{* This template is used for adding/configuring Scheduled Jobs.  *}
<h3>{if $action eq 1}{ts}New Scheduled Job{/ts}{elseif $action eq 2}{ts}Edit Scheduled Job{/ts}{elseif $action eq 128}{ts}Execute Scheduled Job{/ts}{else}{ts}Delete Scheduled Job{/ts}{/if}</h3>
<div class="crm-block crm-form-block crm-job-form-block">
 <div class="crm-submit-buttons">{include file="CRM/common/formButtons.tpl" location="top"}</div>

{if $action eq 8}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts}WARNING: Deleting this Scheduled Job will cause some important site functionality to stop working.{/ts} {ts}Do you want to continue?{/ts}
  </div>
{elseif $action eq 128}
  <div class="messages status no-popup">
      <div class="icon inform-icon"></div>
        {ts}Are you sure you would like to execute this job?{/ts}
  </div>
{else}
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
    <tr class="crm-job-form-block-api_action">
        <td class="label">{ts}API call:{/ts}</td>
        <td>

        <div id="fname"><br/>
        </div>
        <select name="api_entity" type="text" id="api_entity" class="crm-form-select required">
          {crmAPI entity="Entity" var="entities"}
          {foreach from=$entities.values item=entity}
            <option value="{$entity}"{if $entity eq $form.api_entity.value} selected="selected"{/if}>{$entity}</option>
          {/foreach}
        </select>
        {$form.api_action.html}

        <div class="description">{ts}Put in the API method name. You need to enter pieces of full API function name as described in the documentation.{/ts}</div>
<script>
{literal}
CRM.$(function($) {
  function assembleName( ) {

    // dunno yet
    var apiName = "";

    // building prefix
    if( $('#api_action').val() == '' ) {
      $('#fname').html( "<em>API name will start appearing here as you type in fields below.</em>" );
      return;
    }

    var apiPrefix = 'api'

    // building entity
    var apiEntity = $('#api_entity').val().replace( /([A-Z])/g, function($1) {
      return $1.toLowerCase();
    });
    // building action
    var apiAction = $('#api_action').val().replace(/(\_[a-z])/g, function($1) {return $1.toUpperCase().replace('_','');});
    apiName = apiPrefix + '.' + apiEntity + '.' + apiAction;
    $('#fname').text( apiName );
  }

  // bind to different events to build API name live
  $('#api_entity').change(assembleName)
  $('#api_action').change(assembleName).keyup(assembleName);
  assembleName();
});

{/literal}
</script>

      </td>
    </tr>
    <tr class="crm-job-form-block-parameters">
      <td class="label">{$form.parameters.label}<br />{docURL page="Managing Scheduled Jobs" resource="wiki"}</td>
      <td>{$form.parameters.html}</td>
    </tr>
    <tr class="crm-job-form-block-scheduled-run-date">
        <td class="label">{$form.scheduled_run_date.label}</td>
        <td>{$form.scheduled_run_date.html}<br />
            <div dlass="description">{ts}Do not run this job before this date / time. The run frequency selected above will apply thereafter.{/ts}<br />
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

