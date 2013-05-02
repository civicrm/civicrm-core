{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.3                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2013                                |
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
{* this template is used for displaying survey information *}

{if $surveys}
  <div class="action-link">
    <a href="{$addSurveyUrl}" class="button"><span>&raquo; {ts}Add Survey{/ts}</span></a>
  </div>
 {include file="CRM/common/enableDisable.tpl"}
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
        <tr id="row_{$survey.id}" {if $survey.is_active neq 1}class="disabled"{/if}>
    <td>{$survey.title}</td>
          <td>{$survey.campaign_id}</td>
          <td>{$survey.activity_type_id}</td>
          <td>{$survey.release_frequency}</td>
          <td>{$survey.max_number_of_contacts}</td>
          <td>{$survey.default_number_of_contacts}</td>
          <td>{if $survey.is_default}<img src="{$config->resourceBase}i/check.gif" alt="{ts}Default{/ts}" /> {/if}</td>
          <td id="row_{$survey.id}_status">{if $survey.is_active}{ts}Yes{/ts}{else}{ts}No{/ts}{/if}</td>
     <td class="crm-report-optionList-action">{$survey.action}</td>
        </tr>
      {/foreach}
    </table>
  </div>

{else}
  <div class="status">
    <div class="icon inform-icon"></div>&nbsp;{ts}No survey found.{/ts}
  </div>
{/if}
<div class="action-link">
   <a href="{$addSurveyUrl}" class="button"><span>&raquo; {ts}Add Survey{/ts}</span></a>
</div>
