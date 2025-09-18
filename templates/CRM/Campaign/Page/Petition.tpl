{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{* this template is used for displaying survey information *}

{if $surveys}
  <div class="action-link">
    <a href="{$addSurveyUrl}" class="button">
      <span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Add Survey{/ts}</span>
    </a>
  </div>
 {include file="CRM/common/enableDisableApi.tpl"}
 {include file="CRM/common/jsortable.tpl"}
  <div id="surveyList">
    <table id="options" class="display">
      <thead>
        <tr>
          <th>{ts}Survey{/ts}</th>
          <th>{ts}Campaign{/ts}</th>
          <th>{ts}Survey Type{/ts}</th>
          <th>{ts}Release Frequency{/ts}</th>
    <th>{ts}Max Number Of Contacts{/ts}</th>
    <th>{ts}Default Number Of Contacts{/ts}</th>
    <th>{ts}Default?{/ts}</th>
    <th>{ts}Active?{/ts}</th>
    <th id="nosort"></th>
        </tr>
      </thead>
      {foreach from=$surveys item=survey}
        <tr id="survey-{$survey.id}" class="crm-entity {if $survey.is_active neq 1} disabled{/if}">
    <td>{$survey.title}</td>
          <td>{$survey.campaign_id}</td>
          <td>{$survey.activity_type_id}</td>
          <td>{$survey.release_frequency}</td>
          <td>{$survey.max_number_of_contacts}</td>
          <td>{$survey.default_number_of_contacts}</td>
          <td>{icon condition=$survey.is_default}{ts}Default{/ts}{/icon}</td>
          <td id="row_{$survey.id}_status">{if $survey.is_active}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
     <td class="crm-report-optionList-action">{$survey.action}</td>
        </tr>
      {/foreach}
    </table>
  </div>

{else}
  <div class="status">
    {icon icon="fa-info-circle"}{/icon}{ts}None found.{/ts}
  </div>
{/if}
<div class="action-link">
  <a href="{$addSurveyUrl}" class="button">
    <span><i class="crm-i fa-plus-circle" role="img" aria-hidden="true"></i> {ts}Add Survey{/ts}</span>
  </a>
</div>
