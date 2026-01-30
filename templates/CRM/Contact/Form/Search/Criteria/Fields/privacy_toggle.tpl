<table class="form-layout-compressed">
  <tr>
    <td colspan="2">
      {$form.privacy_toggle.html} {help id="privacy_toggle"}
    </td>
  </tr>
  <tr>
    <td>
      {$form.privacy_options.html}
    </td>
    <td style="vertical-align:middle">
      <div id="privacy-operator-wrapper">
        {$form.privacy_operator.html} {help id="privacy_operator"}
      </div>
    </td>
  </tr>
</table>
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $("select#privacy_options").change(function() {
        const showOperator = ($(this).val() && $(this).val().length > 1);
        $('#privacy-operator-wrapper').toggle(showOperator);
      }).change();
    });
  </script>
{/literal}
