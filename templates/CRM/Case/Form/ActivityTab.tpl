{*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
*}
{*this template is used for activity accordion*}
{assign var=caseid value=$caseID}
{if $isForm}
  <details class="crm-accordion-bold crm-case_activities-accordion  crm-case-activities-block" open>
    <summary>
      {ts}Activities{/ts}
    </summary>
    <div id="activities" class="crm-accordion-body padded">
      <details class="crm-accordion-light crm-search_filters-accordion">
        <summary>
          {ts}Search Filters{/ts}
        </summary>
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
                {assign var=activitylow  value="activity_date_low_`$caseID`"}
                {$form.$activitylow.label}<br />
                {$form.$activitylow.html}
              </td>
              <td class="crm-case-caseview-form-block-activity_date_high">
                {assign var=activityhigh  value="activity_date_high_`$caseID`"}
                {$form.$activityhigh.label}<br />
                {$form.$activityhigh.html}
              </td>
              <td class="crm-case-caseview-form-block-activity_type_filter_id">
                {$form.activity_type_filter_id.label}<br />
                {$form.activity_type_filter_id.html}
              </td>
            </tr>
            {if !empty($form.activity_deleted)}
              <tr class="crm-case-caseview-form-block-activity_deleted">
                <td>
                  {$form.activity_deleted.html}{$form.activity_deleted.label}
                </td>
              </tr>
            {/if}
          </table>
        </div>
      </details>
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
  <style>
    {crmAPI var='statuses' entity='OptionValue' action='get' return="color,value" option_limit=0 option_group_id="activity_status"}
    {foreach from=$statuses.values item=status}
    {if !empty($status.color)}
    table#case_id_{$caseID} tr.status-id-{$status.value} {ldelim}
      border-left: 3px solid {$status.color};
    {rdelim}
    {/if}
    {/foreach}
  </style>

{if $isForm}
    </div>
  </details><!-- /.crm-accordion-wrapper -->
{/if}
