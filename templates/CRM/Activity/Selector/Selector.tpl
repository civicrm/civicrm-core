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
<div class="crm-activity-selector-{$context}">
  <div class="crm-accordion-wrapper crm-search_filters-accordion">
    <div class="crm-accordion-header">
    {ts}Filter by Activity{/ts}</a>
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <form><!-- form element is here to fool the datepicker widget -->
      <table class="no-border form-layout-compressed activity-search-options">
        <tr>
          <td class="crm-contact-form-block-activity_type_filter_id crm-inline-edit-field">
            {$form.activity_type_filter_id.label}<br /> {$form.activity_type_filter_id.html|crmAddClass:medium}
          </td>
          <td class="crm-contact-form-block-activity_type_exclude_filter_id crm-inline-edit-field">
            {$form.activity_type_exclude_filter_id.label}<br /> {$form.activity_type_exclude_filter_id.html|crmAddClass:medium}
          </td>
          <td>
            {include file="CRM/Core/DatePickerRange.tpl" fieldName="activity_date_time"}
          </td>
          <td class="crm-contact-form-block-activity_status_filter_id crm-inline-edit-field">
            <label>{ts}Status{/ts}</label><br /> {$form.status_id.html|crmAddClass:medium}
          </td>
        </tr>
      </table>
      </form>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
  <table class="contact-activity-selector-{$context} crm-ajax-table" style="width: 100%;">
    <thead>
    <tr>
      <th data-data="activity_type" class="crm-contact-activity-activity_type">{ts}Type{/ts}</th>
      <th data-data="subject" cell-class="crmf-subject crm-editable" class="crm-contact-activity_subject">{ts}Subject{/ts}</th>
      <th data-data="source_contact_name" class="crm-contact-activity-source_contact">{ts}Added By{/ts}</th>
      <th data-data="target_contact_name" data-orderable="false" class="crm-contact-activity-target_contact">{ts}With{/ts}</th>
      <th data-data="assignee_contact_name" data-orderable="false" class="crm-contact-activity-assignee_contact">{ts}Assigned{/ts}</th>
      <th data-data="activity_date_time" class="crm-contact-activity-activity_date_time">{ts}Date{/ts}</th>
      <th data-data="status_id" cell-class="crmf-status_id crm-editable" cell-data-type="select" cell-data-refresh="true" class="crm-contact-activity-activity_status">{ts}Status{/ts}</th>
      <th data-data="links" data-orderable="false" class="crm-contact-activity-links">&nbsp;</th>
    </tr>
    </thead>
  </table>

  {literal}
    <script type="text/javascript">
      (function($) {
        var context = {/literal}"{$context}"{literal};
        CRM.$('table.contact-activity-selector-' + context).data({
          "ajax": {
            "url": {/literal}'{crmURL p="civicrm/ajax/contactactivity" h=0 q="snippet=4&context=$context&cid=$contactId"}'{literal},
            "data": function (d) {
              var status_id = $('.crm-activity-selector-' + context + ' select#status_id').val() || [];
              d.activity_type_id = $('.crm-activity-selector-' + context + ' select#activity_type_filter_id').val(),
              d.activity_type_exclude_id = $('.crm-activity-selector-' + context + ' select#activity_type_exclude_filter_id').val(),
              d.activity_date_time_relative = $('select#activity_date_time_relative').val(),
              d.activity_date_time_low = $('#activity_date_time_low').val(),
              d.activity_date_time_high = $('#activity_date_time_high').val(),
              d.activity_status_id = status_id.join(',')
            }
          }
        });
        $(function($) {
          $('.activity-search-options :input').change(function(){
            CRM.$('table.contact-activity-selector-' + context).DataTable().draw();
          });
        });
      })(CRM.$);
    </script>
  {/literal}
  <style type="text/css">
    {crmAPI var='statuses' entity='OptionValue' action='get' return="color,value" option_limit=0 option_group_id="activity_status"}
    {foreach from=$statuses.values item=status}
      {if !empty($status.color)}
        table.contact-activity-selector-{$context} tr.status-id-{$status.value} {ldelim}
          border-left: 3px solid {$status.color};
        {rdelim}
      {/if}
    {/foreach}
  </style>
</div>
{include file="CRM/Case/Form/ActivityToCase.tpl" contactID=$contactId}
