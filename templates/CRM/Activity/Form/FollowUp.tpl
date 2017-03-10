<div id="follow-up" class="crm-accordion-wrapper collapsed">
  <div class="crm-accordion-header">
     {ts}Schedule Follow-up{/ts}
  </div><!-- /.crm-accordion-header -->
  <div class="crm-accordion-body">
    <table class="form-layout-compressed">
      <tr class="crm-{$type}activity-form-block-followup_activity_type_id">
	<td class="label">{ts}Schedule Follow-up Activity{/ts}</td>
        <td>{$form.followup_activity_type_id.html}&nbsp;&nbsp;{ts}on{/ts}
        {include file="CRM/common/jcalendar.tpl" elementName=followup_date}
        </td>
      </tr>
      <tr class="crm-{$type}activity-form-block-followup_activity_subject">
        <td class="label">{$form.followup_activity_subject.label}</td>
        <td>{$form.followup_activity_subject.html|crmAddClass:huge}</td>
      </tr>
      <tr class="crm-{$type}activity-form-block-followup_assignee_contact_id">
        <td class="label">
        {$form.followup_assignee_contact_id.label}
        {edit}
        {/edit}
        </td>
        <td>
          {$form.followup_assignee_contact_id.html}
        </td>
      </tr>
    </table>
  </div><!-- /.crm-accordion-body -->
</div><!-- /.crm-accordion-wrapper -->
     