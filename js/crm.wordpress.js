// http://civicrm.org/licensing
CRM.$(function($) {
  $(document)
    .on('dialogopen', function(e) {
      // Make admin bar hide behind popup windows
      $('#adminmenuwrap').css('z-index', '100');
    })
    .on('dialogclose', function(e) {
      if ($('.ui-dialog-content:visible').not(e.target).length < 1) {
        // Restore admin bar position
        $('#adminmenuwrap').css('z-index', '');
      }
    })
    .on('crmWysiwygCreate', function(e, type, editor) {
      if (type === 'ckeditor') {
        editor.on('maximize', function(e) {
          $('#wpadminbar').toggle(e.data === 2);
        });
      }
    });
});
