// http://civicrm.org/licensing

function countSelectedCheckboxes(fldPrefix, form) {
  fieldCount = 0;
  for (i = 0; i < form.elements.length; i++) {
    fpLen = fldPrefix.length;
    if (form.elements[i].type == 'checkbox' && form.elements[i].name.slice(0, fpLen) == fldPrefix && form.elements[i].checked == true) {
      fieldCount++;
    }
  }
  return fieldCount;
}

(function($, _) {
  "use strict";
  var form = 'form.crm-search-form';

  function toggleTaskMenu() {
    var $menu = $('select#task', form);
    $menu.val('').select2('val', '');
    if ($('[name=radio_ts][value=ts_all], .select-row', form).filter(':checked').length) {
      $menu.prop('disabled', false).select2('enable');
    } else {
      $menu.prop('disabled', true).select2('disable');
    }
  }

  $('#crm-container')
    .on('change', '[name=radio_ts], .select-row', toggleTaskMenu)
    .on('crmLoad', toggleTaskMenu)
    .on('change', 'select#task', function() {
      $(this).siblings('input[type=submit]').click();
    });

})(CRM.$, CRM._);
