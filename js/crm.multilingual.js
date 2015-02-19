// http://civicrm.org/licensing
// JS needed for multilingual installations
CRM.$(function($) {
  // This is partially redundant with what the CRM.popup function would do,
  // with a small amount of added functionality,
  // and the difference that this loads unconditionally regardless of ajaxPopupsEnabled setting
  $('body').on('click', 'a.crm-multilingual-edit-button', function(e) {
    var $el = $(this),
      $form = $el.closest('form'),
      $field = $('#' + $el.data('field'), $form);

    CRM.loadForm($el.attr('href'), {
      dialog: {width: '50%', height: '50%'}
    })
      // Sync the primary language field with what the user has entered on the main form
      .on('crmFormLoad', function() {
        $('.default-lang', this).val($field.val());
      })
      .on('crmFormSubmit', function() {
        // Sync the primary language field with what the user has entered in the popup
        $field.val($('.default-lang', this).val());
        $el.trigger('crmPopupFormSuccess');
      });
    e.preventDefault();
  });
});
