// https://civicrm.org/licensing
(function($, _) {
  'use strict';

  // Weird conflict with drupal styles
  $('body').removeClass('toolbar');

  function format(item) {
    var icon = '<span class="ui-icon ui-icon-gear"></span>';
    if (item.icon) {
      icon = '<img src="' + CRM.config.resourceBase + item.icon + '" />';
    }
    return icon + '&nbsp;' + item.text;
  }

  $('#extraPlugins').crmSelect2({
    multiple: true,
    closeOnSelect: false,
    data: CRM.vars.ckConfig.plugins,
    escapeMarkup: _.identity,
    formatResult: format,
    formatSelection: format
  });

  var toolbarModifier = new ToolbarConfigurator.ToolbarModifier( 'editor-basic' );

  toolbarModifier.init(_.noop);

  CKEDITOR.document.getById( 'toolbarModifierWrapper' ).append( toolbarModifier.mainContainer );

  $(function() {
    var selectorOpen = false,
      changedWhileOpen = false;

    $('#toolbarModifierForm')
      .on('submit', function(e) {
        $('.toolbar button:last', '#toolbarModifierWrapper')[0].click();
        $('.configContainer textarea', '#toolbarModifierWrapper').attr('name', 'config');
      })
      .on('change', '.config-param', function(e) {
        changedWhileOpen = true;
        if (!selectorOpen) {
          $('#toolbarModifierForm').submit().block();
        }
      })
      // Debounce the change event so it only fires after the multiselect is closed
      .on('select2-open', 'input.config-param', function(e) {
        selectorOpen = true;
        changedWhileOpen = false;
      })
      .on('select2-close', 'input.config-param', function(e) {
        selectorOpen = false;
        if (changedWhileOpen) {
          $(this).change();
        }
      });
  });

})(CRM.$, CRM._);
