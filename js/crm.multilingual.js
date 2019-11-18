// http://civicrm.org/licensing
// JS needed for multilingual installations
CRM.$(function($) {
  // This is partially redundant with what the CRM.popup function would do,
  // with a small amount of added functionality,
  // and the difference that this loads unconditionally regardless of ajaxPopupsEnabled setting
  $('body').on('click', 'a.crm-multilingual-edit-button', function(e) {
    var $el = $(this),
      $form = $el.closest('form'),
      $field = $('#' + $el.data('field'), $form),
      wysiwyg = $field.hasClass('crm-form-wysiwyg');

    CRM.loadForm($el.attr('href'), {
      dialog: {width: '50%', height: '50%'}
    })
      // Sync the primary language field with what the user has entered on the main form
      .on('crmFormLoad', function() {
        CRM.wysiwyg.setVal($('.default-lang', this), CRM.wysiwyg.getVal($field));
        $('.default-lang', this).triggerHandler('change');
      })
      .on('crmFormSubmit', function() {
        // Sync the primary language field with what the user has entered in the popup
        CRM.wysiwyg.setVal($field, CRM.wysiwyg.getVal($('.default-lang', this)));
        $field.triggerHandler('change');
        $el.trigger('crmPopupFormSuccess');
      });
    e.preventDefault();
  });
});
