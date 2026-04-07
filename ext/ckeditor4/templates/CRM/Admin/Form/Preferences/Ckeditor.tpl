{$form.ckeditor_config.html}
{literal}
  <script type="text/javascript">
    CRM.$(function($) {
      $('#ckeditor_config').appendTo($('#editor_id').parent());
      function showCKEditorConfig() {
        $('#ckeditor_config').css('visibility', $(this).val() == 'CKEditor' ? 'visible' : 'hidden');
      }
      $(':input[name=editor_id]').each(showCKEditorConfig).change(showCKEditorConfig);
    });
  </script>
{/literal}
