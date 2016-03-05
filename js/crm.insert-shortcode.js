// https://civicrm.org/licensing

CRM.$(function($) {
  var $form = $('form.CRM_Core_Form_ShortCode');

  function changeComponent() {
    var component = $(this).val(),
      entities = $(this).data('entities');

    $('.shortcode-param[data-components]', $form).each(function() {
      $(this).toggle($.inArray(component, $(this).data('components')) > -1);

      if (entities[component]) {
        $('input[name=entity]')
          .val('')
          .data('key', entities[component].key)
          .data('select-params', null)
          .data('api-params', null)
          .crmEntityRef(entities[component]);
      }
    });
  }

  function close() {
    $form.closest('.ui-dialog-content').dialog('close');
  }

  function insert() {
    var code = '[civicrm';
    $('.shortcode-param:visible', $form).each(function() {
      var $el = $('input:checked, select, input.crm-form-entityref', this);
      code += ' ' + $el.data('key') + '="' + $el.val() + '"';
    });
    window.send_to_editor(code + ']');
    close();
  }

  $('select[name=component]', $form).each(changeComponent).change(changeComponent);

  $form.closest('.ui-dialog-content').dialog('option', 'buttons', [
    {
      text: ts("Insert"),
      icons: {primary: "fa-check"},
      click: insert
    },
    {
      text: ts("Cancel"),
      icons: {primary: "fa-times"},
      click: close
    }
  ]);
});
