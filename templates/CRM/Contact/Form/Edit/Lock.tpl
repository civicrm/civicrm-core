{literal}
<script type="text/javascript">
cj(function() {
  if (cj('#update_modified_date').length == 0) {
    return;
  }
  cj('<br>')
    .appendTo(cj('#update_modified_date'));
  cj('<button>')
    .text("{/literal}{ts}Save Anyway{/ts}{literal}")
    .click(function() {
      cj('input[name="modified_date"]').val(
              cj('#update_modified_date').attr('data:latest_modified_date')
      );
      cj('.crm-form-block .form-submit.default').first().click();
      return false;
    })
    .appendTo(cj('#update_modified_date'))
    ;
  cj('<button>')
    .text("{/literal}{ts}Reload Page{/ts}{literal}")
    .click(function() {
      window.location = CRM.url('civicrm/contact/add', {
        reset: 1,
        action: 'update',
        cid: {/literal}{$contactId}{literal}
      });
      return false;
    })
    .appendTo(cj('#update_modified_date'))
    ;
});
</script>
{/literal}