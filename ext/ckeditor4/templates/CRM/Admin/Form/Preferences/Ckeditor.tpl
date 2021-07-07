{$form.ckeditor_config.html}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('#ckeditor_config').appendTo($('#editor_id').parent());
      function showCKEditorConfig() {
        $('.crm-preferences-display-form-block-editor_id .crm-button').toggle($(this).val() == 'CKEditor');
      }
      $('select[name=editor_id]').each(showCKEditorConfig).change(showCKEditorConfig);
    });
  </script>
{/literal}
