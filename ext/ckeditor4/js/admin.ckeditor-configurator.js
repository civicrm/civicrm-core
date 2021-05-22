// https://civicrm.org/licensing
(function($, _) {
  'use strict';
  /* jshint validthis: true */

  var configRowTpl = _.template($('#config-row-tpl').html()),
    options;

  // Weird conflict with drupal styles
  $('body').removeClass('toolbar');

  function format(item) {
    var icon = '<span class="ui-icon ui-icon-gear"></span>';
    if (item.icon) {
      icon = '<img src="' + CRM.config.resourceBase + item.icon + '" />';
    }
    return icon + '&nbsp;' + item.text;
  }

  function initOptions(data) {
    options = _.filter(data, function(n) {
      return $.inArray(n.id, CRM.vars.ckConfig.blacklist) < 0;
    });
    addOption();
    $.each(CRM.vars.ckConfig.settings, function(key, val) {
      if ($.inArray(key, CRM.vars.ckConfig.blacklist) < 0) {
        var $opt = $('.crm-config-option-row:last input.crm-config-option-name');
        $opt.val(key).change();
        $opt.siblings('span').find(':input').val(val);
      }
    });
  }

  function changeOptionName() {
    var $el = $(this),
      name = $el.val();
    $el.next('span').remove();
    if (name) {
      if (($('input.crm-config-option-name').filter(function() {return !this.value;})).length < 1) {
        addOption();
      }
      var type = $el.select2('data').type;
      if (type === 'Boolean') {
        $el.after('<span>&nbsp; = &nbsp;<select class="crm-form-select" name="config_' + name + '"><option value="false">false</option><option value="true">true</option></select></span>');
      }
      else {
        $el.after('<span>&nbsp; = &nbsp;<input class="crm-form-text ' + (type==='Number' ? 'eight" type="number"' : 'huge" type="text"') + ' name="config_' + name + '"/></span>');
        $el.next('span').find('input.crm-form-text[type=text]').change(validateJson);
      }
    } else {
      $el.closest('div').remove();
    }
  }

  function getOptionList() {
    var list = [];
    _.forEach(options, function(option) {
      var opt = _.cloneDeep(option);
      if ($('[name="config_' + opt.id + '"]').length) {
        opt.disabled = true;
      }
      list.push(opt);
    });
    return {results: list, text: 'id'};
  }

  function validateJson() {
    // TODO: strict json isn't required so we can't use JSON.parse for error checking. Need something like angular.eval.
  }

  function addOption() {
    $('#crm-custom-config-options').append($(configRowTpl({})));
    $('.crm-config-option-row:last input.crm-config-option-name', '#crm-custom-config-options').crmSelect2({
      data: getOptionList,
      formatSelection: function(field) {
        return '<strong>' + field.id + '</strong> (' + field.type + ')';
      },
      formatResult: function(field) {
        return '<strong>' + field.id + '</strong> (' + field.type + ')' +
          '<div class="api-field-desc">' + field.description + '</div>';
      }
    });
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

    $('#CKEditorConfig')
      .on('submit', function(e) {
        $('.toolbar button:last', '#toolbarModifierWrapper')[0].click();
        $('.configContainer textarea', '#toolbarModifierWrapper').attr('name', 'config');
      })
      .on('change', '.config-param', function(e) {
        changedWhileOpen = true;
        if (!selectorOpen) {
          $('#_qf_CKEditorConfig_submit-bottom').click();
          $('#CKEditorConfig').block();
        }
      })
      .on('change', 'input.crm-config-option-name', changeOptionName)
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

    $.getJSON(CRM.config.resourceBase + 'ext/ckeditor4/js/ck-options.json', null, initOptions);
  });

})(CRM.$, CRM._);
