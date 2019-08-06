{*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2019                                |
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
{*this template is used for activity accordion*}
{assign var=caseid value=$caseID}
{if isset($isForm) and $isForm}
  <div class="crm-accordion-wrapper crm-case_activities-accordion  crm-case-activities-block">
    <div class="crm-accordion-header">
      {ts}Activities{/ts}
    </div>

    <div id="activities" class="crm-accordion-body">
    <div class="crm-accordion-wrapper crm-accordion-inner crm-search_filters-accordion collapsed">
      <div class="crm-accordion-header">
        {ts}Search Filters{/ts}
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
          </tr>
          <tr>
            <td class="crm-case-caseview-form-block-activity_date_low">
              {assign var=activitylow  value=activity_date_low_$caseID}
              {$form.$activitylow.label}<br />
              {$form.$activitylow.html}
            </td>
            <td class="crm-case-caseview-form-block-activity_date_high">
              {assign var=activityhigh  value=activity_date_high_$caseID}
              {$form.$activityhigh.label}<br />
              {$form.$activityhigh.html}
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
{/if}

    <table id="case_id_{$caseid}"  class="nestedActivitySelector crm-ajax-table" data-page-length="10">
      <thead><tr>
        <th data-data="activity_date_time" class="crm-case-activities-date">{ts}Date{/ts}</th>
        <th data-data="subject" cell-class="crmf-subject crm-editable" class="crm-case-activities-subject">{ts}Subject{/ts}</th>
        <th data-data="type" class="crm-case-activities-type">{ts}Type{/ts}</th>
        <th data-data="target_contact_name" class="crm-case-activities-with">{ts}With{/ts}</th>
        <th data-data="source_contact_name" class="crm-case-activities-assignee">{ts}Reporter{/ts}</th>
        <th data-data="assignee_contact_name" class="crm-case-activities-assignee">{ts}Assignee{/ts}</th>
        <th data-data="status_id" cell-class="crmf-status_id crm-editable" cell-data-type="select" cell-data-refresh=1 class="crm-case-activities-status">{ts}Status{/ts}</th>
        <th data-data="links" data-orderable="false" class="crm-case-activities-status">&nbsp;</th>
      </tr></thead>
    </table>
  {literal}
    <script type="text/javascript">
      (function($) {
        var caseId = {/literal}{$caseID}{literal};
        CRM.$('table#case_id_' + caseId).data({
          "ajax": {
            "url": {/literal}'{crmURL p="civicrm/ajax/activity" h=0 q="snippet=4&caseID=$caseID&cid=$contactID&userID=$userID"}'{literal},
            "data": function (d) {
              d.status_id = $("select#status_id_" + caseId).val(),
              d.reporter_id = $("select#reporter_id_" + caseId).val(),
              d.activity_type_id = $("select#activity_type_filter_id_" + caseId).val(),
              d.activity_date_low = $("#activity_date_low_" + caseId).val(),
              d.activity_date_high = $("#activity_date_high_" + caseId).val(),
              d.activity_deleted = ($("#activity_deleted_" + caseId).prop('checked')) ? 1 : 0;
            }
          }
        });
        $(function($) {
          $('#searchOptions :input').change(function(){
            CRM.$('table#case_id_' + caseId).DataTable().draw();
          });
        });
      })(CRM.$);
    </script>
  {/literal}
  <style type="text/css">
    {crmAPI var='statuses' entity='OptionValue' action='get' return="color,value" option_limit=0 option_group_id="activity_status"}
    {foreach from=$statuses.values item=status}
    {if !empty($status.color)}
    table#case_id_{$caseID} tr.status-id-{$status.value} {ldelim}
      border-left: 3px solid {$status.color};
    {rdelim}
    {/if}
    {/foreach}
  </style>

{if isset($isForm) and $isForm}
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
{/if}
