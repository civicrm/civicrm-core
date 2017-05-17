{*
<div class="crm-activity-selector-{$context}">
  <div class="crm-accordion-wrapper crm-search_filters-accordion">
    <div class="crm-accordion-header">
    {ts}Filter by Activity Type{/ts}</a>
    </div><!-- /.crm-accordion-header -->
    <div class="crm-accordion-body">
      <div class="no-border form-layout-compressed activity-search-options">
          <div class="crm-contact-form-block-activity_type_filter_id crm-inline-edit-field">
            {$form.activity_type_filter_id.label} {$form.activity_type_filter_id.html|crmAddClass:big}
          </div>
          <div class="crm-contact-form-block-activity_type_exclude_filter_id crm-inline-edit-field">
            {$form.activity_type_exclude_filter_id.label} {$form.activity_type_exclude_filter_id.html|crmAddClass:big}
          </div>
      </div>
    </div><!-- /.crm-accordion-body -->
  </div><!-- /.crm-accordion-wrapper -->
 *}
  <table class="contact-activity-selector-orgtasks{* crm-ajax-table*}">
    <thead>
    <tr>
      <th data-data="activity_type" class="crm-contact-activity-activity_type" data-orderable="false">{ts}Type{/ts}</th>
      <th data-data="subject" cell-class="crmf-subject crm-editable" data-orderable="false" class="crm-contact-activity_subject">{ts}Subject{/ts}</th>
      <th data-data="source_contact_name" data-orderable="false" class="crm-contact-activity-source_contact">{ts}Added By{/ts}</th>
      <th data-data="target_contact_name" data-orderable="false" class="crm-contact-activity-target_contact">{ts}With{/ts}</th>
      <th data-data="assignee_contact_name" data-orderable="false" class="crm-contact-activity-assignee_contact">{ts}Assigned{/ts}</th>
      <th data-data="activity_date_time" class="crm-contact-activity-activity_date" data-orderable="false">{ts}Date{/ts}</th>
      <th data-data="status_id" cell-class="crmf-status_id crm-editable" cell-data-type="select" cell-data-refresh="true" class="crm-contact-activity-activity_status" data-orderable="false">{ts}Status{/ts}</th>
      <th data-data="links" data-orderable="false" class="crm-contact-activity-links">&nbsp;</th>
    </tr>
    </thead>
    {foreach from=$activities item="currActivity"}
      <tr class="{cycle values="odd-row,even-row"}">
        <td>{$currActivity.activity_type.label}</td>
        <td>{$currActivity.subject}</td>
        <td>
          {include file="CRM/Orgtaskskr/Page/ContactSummary.tpl" contact=$currActivity.creator}
        </td>
        <td>
          {foreach from=$currActivity.targets item="currTarget"}
            {include file="CRM/Orgtaskskr/Page/ContactSummary.tpl" contact=$currTarget}
          {/foreach}
        </td>
        <td>
          {foreach from=$currActivity.assigned item="currTarget"}
            {include file="CRM/Orgtaskskr/Page/ContactSummary.tpl" contact=$currTarget}
          {/foreach}
        </td>
        <td>{$currActivity.date}</td>
        <td>{$currActivity.status.label}</td>
        <td></td>
      </tr>
    {/foreach}
  </table>

</div>
