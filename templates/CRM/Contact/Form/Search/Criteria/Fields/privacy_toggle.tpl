<table class="form-layout-compressed">
  <tr>
    <td colspan="2">
      {$form.privacy_toggle.html} {help id="id-privacy"}
    </td>
  </tr>
  <tr>
    <td>
      {$form.privacy_options.html}
    </td>
    <td style="vertical-align:middle">
      <div id="privacy-operator-wrapper">
        {$form.privacy_operator.html} {help id="privacy-operator"}
      </div>
    </td>
  </tr>
</table>
{literal}
  <script type="text/javascript">
    cj("select#privacy_options").change(function () {
      if (cj(this).val() && cj(this).val().length > 1) {
        cj('#privacy-operator-wrapper').show();
      } else {
        cj('#privacy-operator-wrapper').hide();
      }
    }).change();
  </script>
{/literal}
