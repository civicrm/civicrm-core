{literal}
<script type="text/javascript">
CRM.$(function($) {
  if ($('#update_modified_date').length == 0) {
    return;
  }
  $('<br>')
    .appendTo($('#update_modified_date'));
  $('<button>')
    .text("{/literal}{ts escape='js'}Save Anyway{/ts}{literal}")
    .click(function() {
      $('input[name="modified_date"]').val(
              $('#update_modified_date').attr('data:latest_modified_date')
      );
      $('.crm-form-block .crm-form-submit.default').first().click();
      return false;
    })
    .appendTo($('#update_modified_date'))
    ;
  $('<button>')
    .text("{/literal}{ts escape='js'}Reload Page{/ts}{literal}")
    .click(function() {
      window.location.href = CRM.url('civicrm/contact/add', {
        reset: 1,
        action: 'update',
        cid: {/literal}{$contactId}{literal}
      });
      return false;
    })
    .appendTo($('#update_modified_date'))
    ;
});
</script>
{/literal}
