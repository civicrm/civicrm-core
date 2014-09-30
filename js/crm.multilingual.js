// http://civicrm.org/licensing
// JS needed for multilingual installations
CRM.$(function($) {
  // This is largely redundant with what the CRM.popup function would do,
  // with the difference that this loads unconditionally regardless of ajaxPopupsEnabled setting
  $('body').on('click', 'a.crm-multilingual-edit-button', function(e) {
    var $el = $(this),
      $form = $el.closest('form'),
      $field = $('#' + $el.data('field'), $form);

    CRM.loadForm($el.attr('href'), {
      dialog: {width: '50%', height: '50%'}
    })
      .on('crmFormLoad', function() {
        $('.default-lang', this).val($field.val());
      })
      .on('crmFormSubmit', function() {
        $field.val($('.default-lang', this).val());
        $el.trigger('crmPopupFormSuccess');
      });
    e.preventDefault();
  });
});
