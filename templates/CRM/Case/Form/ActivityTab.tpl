{*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.4                                                |
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
{this template is used for activity accordion}
<div class="crm-accordion-wrapper crm-case_activities-accordion  crm-case-activities-block">
  <div class="crm-accordion-header">
    {ts}Activities{/ts}
  </div>
  <div id="activities" class="crm-accordion-body">
    <div class="crm-accordion-wrapper crm-accordion-inner crm-search_filters-accordion collapsed">
      <div class="crm-accordion-header">
        {ts}Search Filters{/ts}</a>
      </div><!-- /.crm-accordion-header -->
      <div class="crm-accordion-body">
        <table class="no-border form-layout-compressed" id="searchOptions">
          <tr>
            <td class="crm-case-caseview-form-block-repoter_id"colspan="2"><label for="reporter_id">{ts}Reporter/Role{/ts}</label><br />
              {$form.reporter_id.html|crmAddClass:twenty}
            </td>
            <td class="crm-case-caseview-form-block-status_id"><label for="status_id">{$form.status_id.label}</label><br />
              {$form.status_id.html}
            </td>
            <td style="vertical-align: bottom;">
              <span class="crm-button"><input class="form-submit default" name="_qf_Basic_refresh" value="Search" type="button" onclick="buildCaseActivities( true )"; /></span>
            </td>
          </tr>
          <tr>
            <td class="crm-case-caseview-form-block-activity_date_low">
              {$form.activity_date_low.label}<br />
            {include file="CRM/common/jcalendar.tpl" elementName=activity_date_low}
            </td>
            <td class="crm-case-caseview-form-block-activity_date_high">
              {$form.activity_date_high.label}<br />
            {include file="CRM/common/jcalendar.tpl" elementName=activity_date_high}
            </td>
            <td class="crm-case-caseview-form-block-activity_type_filter_id">
              {$form.activity_type_filter_id.label}<br />
              {$form.activity_type_filter_id.html}
            </td>
          </tr>
          {if $form.activity_deleted}
            <tr class="crm-case-caseview-form-block-activity_deleted">
              <td>
                {$form.activity_deleted.html}{$form.activity_deleted.label}
              </td>
            </tr>
          {/if}
        </table>
      </div><!-- /.crm-accordion-body -->
    </div><!-- /.crm-accordion-wrapper -->

    <table id="activities-selector"  class="nestedActivitySelector">
      <thead><tr>
        <th class='crm-case-activities-date'>{ts}Date{/ts}</th>
        <th class='crm-case-activities-subject'>{ts}Subject{/ts}</th>
        <th class='crm-case-activities-type'>{ts}Type{/ts}</th>
        <th class='crm-case-activities-with'>{ts}With{/ts}</th>
        <th class='crm-case-activities-assignee'>{ts}Reporter / Assignee{/ts}</th>
        <th class='crm-case-activities-status'>{ts}Status{/ts}</th>
        <th class='crm-case-activities-status' id="nosort">&nbsp;</th>
        <th class='hiddenElement'>&nbsp;</th>
      </tr></thead>
    </table>

  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
