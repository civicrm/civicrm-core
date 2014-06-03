// http://civicrm.org/licensing

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

  function countCheckboxes() {
    $('label[for*=ts_sel] span', form).text($('input.select-row:checked', form).length);
  }

  $('#crm-container')
    .on('change', '[name=radio_ts], .select-row', toggleTaskMenu)
    .on('change', 'input.select-row', countCheckboxes)
    .on('crmLoad', toggleTaskMenu)
    .on('click', 'input.select-row, input.select-rows', function() {
      $(this).closest('form').find('input[name=radio_ts][value=ts_sel]').prop('checked', true);
    })
    .on('change', 'select#task', function() {
      $(this).siblings('input[type=submit]').click();
    });

})(CRM.$, CRM._);
