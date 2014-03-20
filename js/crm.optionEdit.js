// https://civicrm.org/licensing
// Enable administrators to edit option lists in a dialog
jQuery(function($) {
  var optionsChanged;
  $('body')
    // Edit option lists
    .on('click', 'a.crm-option-edit-link', CRM.popup)
    .on('crmPopupOpen crmPopupFormSuccess', 'a.crm-option-edit-link', function(e) {
      optionsChanged = e.type === 'crmPopupFormSuccess';
    })
    .on('crmPopupClose', 'a.crm-option-edit-link', function() {
      if (optionsChanged) {
        $(this).trigger('crmOptionsEdited');
        var $elects = $('select[data-option-edit-path="' + $(this).data('option-edit-path') + '"]');
        if ($elects.data('api-entity') && $elects.data('api-field')) {
          CRM.api3($elects.data('api-entity'), 'getoptions', {sequential: 1, field: $elects.data('api-field')})
            .done(function (data) {
              CRM.utils.setOptions($elects, data.values);
            });
        }
      }
    })
});
