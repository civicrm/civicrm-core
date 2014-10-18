(function($, _, undefined) {
  var
    entity,
    action,
    actions = {values: ['get']},
    fields = [],
    getFieldData = {},
    options = {},
    params = {},
    smartyStub,
    fieldTpl = _.template($('#api-param-tpl').html()),
    optionsTpl = _.template($('#api-options-tpl').html()),
    returnTpl = _.template($('#api-return-tpl').html()),
    chainTpl = _.template($('#api-chain-tpl').html()),

    // Operators with special properties
    BOOL = ['IS NULL', 'IS NOT NULL'],
    TEXT = ['LIKE', 'NOT LIKE'],
    MULTI = ['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'];

  /**
   * Call prettyPrint function if it successfully loaded from the cdn
   */
  function prettyPrint() {
    if (window.prettyPrint) {
      window.prettyPrint();
    }
  }

  /**
   * Add a "fields" row
   * @param name
   */
  function addField(name) {
    $('#api-params').append($(fieldTpl({name: name || ''})));
    var $row = $('tr:last-child', '#api-params');
    $('input.api-param-name', $row).crmSelect2({
      data: fields.concat({id: '-', text: ts('Other') + '...'})
    }).change();
  }

  /**
   * Add a new "options" row
   * @param name
   */
  function addOptionField(name) {
    if ($('.api-options-row', '#api-params').length) {
      $('.api-options-row:last', '#api-params').after($(optionsTpl({})));
    } else {
      $('#api-params').append($(optionsTpl({})));
    }
    var $row = $('.api-options-row:last', '#api-params');
    $('.api-option-name', $row).crmSelect2({data: [
      {id: 'limit', text: 'limit'},
      {id: 'offset', text: 'offset'},
      {id: 'sort', text: 'sort'},
      {id: 'metadata', text: 'metadata'},
      {id: '-', text: ts('Other') + '...'}
    ]});
  }

  /**
   * Add an "api chain" row
   */
  function addChainField() {
    $('#api-params').append($(chainTpl({})));
    var $row = $('tr:last-child', '#api-params');
    $('.api-chain-entity', $row).crmSelect2({
      formatSelection: function(item) {
        return '<span class="icon ui-icon-link"></span> API ' + item.text;
      },
      placeholder: '<span class="icon ui-icon-link"></span> ' + ts('Entity'),
      escapeMarkup: function(m) {return m}
    });
  }

  /**
   * Fetch fields for entity+action
   */
  function getFields(changedElement) {
    var required = [];
    fields = [];
    options = getFieldData = {};
    // Special case for getfields
    if (action === 'getfields') {
      fields.push({
        id: 'api_action',
        text: 'Action'
      });
      options.api_action = _.reduce(actions.values, function(ret, item) {
        ret[item] = item;
        return ret;
      }, {});
      showFields(['api_action']);
      return;
    }
    CRM.api3(entity, 'getFields', {'api_action': action, options: {get_options: 'all'}}).done(function(data) {
      getFieldData = data.values;
      _.each(data.values, function(field) {
        if (field.name) {
          fields.push({
            id: field.name,
            text: field.title || field.name,
            required: field['api.required'] || false
          });
          if (field['api.required']) {
            required.push(field.name);
          }
          if (field.options) {
            options[field.name] = field.options;
          }
        }
      });
      if ($(changedElement).is('#api-entity') && data.deprecated) {
        CRM.alert(data.deprecated, entity + ' Deprecated');
      }
      showFields(required);
      if (action === 'get' || action === 'getsingle') {
        showReturn();
      }
    });
  }

  /**
   * For "get" actions show the "return" options
   */
  function showReturn() {
    $('#api-params').prepend($(returnTpl({})));
    $('#api-return-value').crmSelect2({data: fields, multiple: true});
  }

  /**
   * Fetch actions for entity
   */
  function getActions() {
    if (entity) {
      $('#api-action').addClass('loading');
      CRM.api3(entity, 'getactions').done(function(data) {
        actions = data;
        populateActions();
      });
    } else {
      actions = {values: ['get']};
      populateActions();
    }
  }

  function isActionDeprecated(action) {
    return !!(typeof actions.deprecated === 'object' && actions.deprecated[action]);
  }

  function renderAction(option) {
    return isActionDeprecated(option.id) ? '<span class="strikethrough">' + option.text + '</span>' : option.text;
  }

  /**
   * Called after getActions to populate action list
   * @param el
   */
  function populateActions(el) {
    var val = $('#api-action').val();
    $('#api-action').removeClass('loading').select2({
      data: _.transform(actions.values, function(ret, item) {ret.push({text: item, id: item})}),
      formatSelection: renderAction,
      formatResult: renderAction
    });
    // If previously selected action is not available, set it to 'get' if possible
    if (_.indexOf(actions.values, val) < 0) {
      $('#api-action').select2('val', _.indexOf(actions.values, 'get') < 0 ? actions.values[0] : 'get', true);
    }
  }

  function onChangeAction(action) {
    if (isActionDeprecated(action)) {
      CRM.alert(actions.deprecated[action], action + ' deprecated');
    }
  }

  /**
   * Called after getfields to show buttons and required fields
   * @param required
   */
  function showFields(required) {
    $('#api-params').empty();
    $('#api-param-buttons').show();
    if (required.length) {
      _.each(required, addField);
    } else {
      addField();
    }
  }

  /**
   * Render value input as a textfield, option list, entityRef, or hidden,
   * Depending on selected param name and operator
   */
  function renderValueField() {
    var $row = $(this).closest('tr'),
      name = $('input.api-param-name', $row).val(),
      operator = $('.api-param-op', $row).val(),
      operatorType = $.inArray(operator, MULTI) > -1 ? 'multi' : ($.inArray(operator, BOOL) > -1 ? 'bool' : 'single'),
      $valField = $('input.api-param-value', $row),
      currentVal = $valField.val();
    // Boolean fields only have 1 possible value
    if (operatorType == 'bool') {
      if ($valField.data('select2')) {
        $valField.select2('destroy');
      }
      $valField.css('visibility', 'hidden').val('1');
      return;
    }
    $valField.css('visibility', '');
    // Option list or entityRef input
    if ((options[name] || (getFieldData[name] && getFieldData[name].FKApiName)) && $.inArray(operator, TEXT) < 0) {
      // Reset value before switching to a select from something else
      if ($(this).is('.api-param-name') || !$valField.data('select2')) {
        $valField.val('');
      }
      // When switching from multi-select to single select
      else if (operatorType == 'single' && currentVal.indexOf(',') > -1) {
        $valField.val(currentVal.split(',')[0]);
      }
      // Select options
      if (options[name]) {

        $valField.select2({
          multiple: (operatorType === 'multi'),
          data: _.map(options[name], function (value, key) {
            return {id: key, text: value};
          })
        });
      }
      // EntityRef
      else {
        $valField.crmEntityRef({
          entity: getFieldData[name].FKApiName,
          select: {multiple: (operatorType === 'multi')}
        });
      }
      return;
    }
    // Plain text input
    if ($valField.data('select2')) {
      $valField.select2('destroy');
    }
  }

  /**
   * Attempt to parse a string into a value of the intended type
   * @param val string
   * @param makeArray bool
   */
  function evaluate(val, makeArray) {
    try {
      if (!val.length) {
        return makeArray ? [] : '';
      }
      var first = val.charAt(0),
        last = val.slice(-1);
      // Simple types
      if (val === 'true' || val === 'false' || val === 'null') {
        return eval(val);
      }
      // Quoted strings
      if ((first === '"' || first === "'") && last === first) {
        return val.slice(1, -1);
      }
      // Parse json
      if ((first === '[' && last === ']') || (first === '{' && last === '}')) {
        return eval('(' + val + ')');
      }
      // Transform csv to array
      if (makeArray) {
        var result = [];
        $.each(val.split(','), function(k, v) {
          result.push(evaluate($.trim(v)) || v);
        });
        return result;
      }
      // Integers - quote any number that starts with 0 to avoid oddities
      if (!isNaN(val) && val.search(/[^\d]/) < 0 && (val.length === 1 || first !== '0')) {
        return parseInt(val, 10);
      }
      // Ok ok it's really a string
      return val;
    } catch(e) {
      // If eval crashed return undefined
      return undefined;
    }
  }

  /**
   * Format value to look like php code
   * @param val
   */
  function phpFormat(val) {
    var ret = '';
    if ($.isPlainObject(val)) {
      $.each(val, function(k, v) {
        ret += (ret ? ', ' : '') + "'" + k + "' => " + phpFormat(v);
      });
      return 'array(' + ret + ')';
    }
    if ($.isArray(val)) {
      $.each(val, function(k, v) {
        ret += (ret ? ', ' : '') + phpFormat(v);
      });
      return 'array(' + ret + ')';
    }
    return JSON.stringify(val).replace(/\$/g, '\\$');
  }

  /**
   * Smarty doesn't support array literals so we provide a stub
   * @param js string
   */
  function smartyFormat(js, key) {
    if (js.indexOf('[') > -1 || js.indexOf('{') > -1) {
      smartyStub = true;
      return '$' + key.replace(/[. -]/g, '_');
    }
    return js;
  }

  /**
   * Create the params array from user input
   * @param e
   */
  function buildParams(e) {
    params = {};
    $('.api-param-checkbox:checked').each(function() {
      params[this.name] = 1;
    });
    $('input.api-param-value, input.api-option-value').each(function() {
      var $row = $(this).closest('tr'),
        op = $('select.api-param-op', $row).val() || '=',
        name = $('input.api-param-name', $row).val(),
        val = evaluate($(this).val(), $.inArray(op, MULTI) > -1);

      // Ignore blank values for the return field
      if ($(this).is('#api-return-value') && !val) {
        return;
      }
      // Special syntax for api chaining
      if (!name && $('select.api-chain-entity', $row).val()) {
        name = 'api.' + $('select.api-chain-entity', $row).val() + '.' + $('select.api-chain-action', $row).val();
      }
      // Special handling for options
      if ($(this).is('.api-option-value')) {
        op = $('input.api-option-name', $row).val();
        if (op) {
          name = 'options';
        }
      }
      if (name && val !== undefined) {
        params[name] = op === '=' ? val : (params[name] || {});
        if (op !== '=') {
          params[name][op] = val;
        }
        if ($(this).hasClass('crm-error')) {
          clearError(this);
        }
      }
      else if (name && (!e || e.type !== 'keyup')) {
        setError(this);
      }
    });
    if (entity && action) {
      formatQuery();
    }
  }

  function setError(el) {
    if (!$(el).hasClass('crm-error')) {
      var msg = ts('Syntax error: input should be valid JSON or a quoted string.');
      $(el)
        .addClass('crm-error')
        .css('width', '82%')
        .attr('title', msg)
        .before('<div class="icon red-icon ui-icon-alert" title="'+msg+'"/>')
        .tooltip();
    }
  }

  function clearError(el) {
    $(el)
      .removeClass('crm-error')
      .attr('title', '')
      .css('width', '85%')
      .tooltip('destroy')
      .siblings('.ui-icon-alert').remove();
  }

  function formatQuery() {
    var i = 0, q = {
      smarty: "{crmAPI var='result' entity='" + entity + "' action='" + action + "'",
      php: "$result = civicrm_api3('" + entity + "', '" + action + "'",
      json: "CRM.api3('" + entity + "', '" + action + "'",
      drush: "drush cvapi " + entity + '.' + action + ' ',
      wpcli: "wp cv api " + entity + '.' + action + ' ',
      rest: CRM.config.resourceBase + "extern/rest.php?entity=" + entity + "&action=" + action + "&json=" + JSON.stringify(params) + "&api_key=yoursitekey&key=yourkey"
    };
    smartyStub = false;
    $.each(params, function(key, value) {
      var js = JSON.stringify(value);
      if (!i++) {
        q.php += ", array(\n";
        q.json += ", {\n";
      } else {
        q.json += ",\n";
      }
      q.php += "  '" + key + "' => " + phpFormat(value) + ",\n";
      q.json += "  \"" + key + '": ' + js;
      q.smarty += ' ' + key + '=' + smartyFormat(js, key);
      // FIXME: This is not totally correct cli syntax
      q.drush += key + '=' + js + ' ';
      q.wpcli += key + '=' + js + ' ';
    });
    if (i) {
      q.php += ")";
      q.json += "\n}";
    }
    q.php += ");";
    q.json += ").done(function(result) {\n  // do something\n});";
    q.smarty += "}\n{foreach from=$result.values item=" + entity.toLowerCase() + "}\n  {$" + entity.toLowerCase() + ".some_field}\n{/foreach}";
    if (action.indexOf('get') < 0) {
      q.smarty = '{* Smarty API only works with get actions *}';
    } else if (smartyStub) {
      q.smarty = "{* Smarty does not have a syntax for array literals; assign complex variables from php *}\n" + q.smarty;
    }
    $.each(q, function(type, val) {
      $('#api-' + type).removeClass('prettyprinted').text(val);
    });
    prettyPrint();
  }

  function submit(e) {
    e.preventDefault();
    if (!entity || !action) {
      alert(ts('Select an entity.'));
      return;
    }
    if (action.indexOf('get') < 0 && action != 'check') {
      var msg = action === 'delete' ? ts('This will delete data from CiviCRM. Are you sure?') : ts('This will write to the database. Continue?');
      CRM.confirm({title: ts('Confirm %1', {1: action}), message: msg}).on('crmConfirm:yes', execute);
    } else {
      execute();
    }
  }

  /**
   * Execute api call and display the results
   * Note: We have to manually execute the ajax in order to add the secret extra "prettyprint" param
   */
  function execute() {
    $('#api-result').html('<div class="crm-loading-element"></div>');
    $.ajax({
      url: CRM.url('civicrm/ajax/rest'),
      data: {
        entity: entity,
        action: action,
        prettyprint: 1,
        json: JSON.stringify(params)
      },
      type: action.indexOf('get') < 0 ? 'POST' : 'GET',
      dataType: 'text'
    }).done(function(text) {
      $('#api-result').addClass('prettyprint').removeClass('prettyprinted').text(text);
      prettyPrint();
    });
  }

  $(document).ready(function() {
    $('#api-entity').crmSelect2({
      // Add strikethough class to selection to indicate deprecated apis
      formatSelection: function(option) {
        return $(option.element).hasClass('strikethrough') ? '<span class="strikethrough">' + option.text + '</span>' : option.text;
      }
    });
    $('form#api-explorer')
      .on('change', '#api-entity, #api-action', function() {
        entity = $('#api-entity').val();
        action = $('#api-action').val();
        if ($(this).is('#api-entity')) {
          getActions();
        } else {
          onChangeAction(action);
        }
        if (entity && action) {
          $('#api-params').html('<tr><td colspan="4" class="crm-loading-element"></td></tr>');
          $('#api-params-table thead').show();
          getFields(this);
          buildParams();
        } else {
          $('#api-params, #api-generated pre').empty();
          $('#api-param-buttons, #api-params-table thead').hide();
        }
      })
      .on('change keyup', 'input.api-input, #api-params select', buildParams)
      .on('submit', submit);
    $('#api-params')
      .on('change', 'input.api-param-name, select.api-param-op', renderValueField)
      .on('change', 'input.api-param-name, .api-option-name', function() {
        if ($(this).val() === '-') {
          $(this).select2('destroy');
          $(this).val('').focus();
        }
      })
      .on('click', '.api-param-remove', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        buildParams();
      });
    $('#api-params-add').on('click', function(e) {
      e.preventDefault();
      addField();
    });
    $('#api-option-add').on('click', function(e) {
      e.preventDefault();
      addOptionField();
    });
    $('#api-chain-add').on('click', function(e) {
      e.preventDefault();
      addChainField();
    });
    $('#api-entity').change();
  });
}(CRM.$, CRM._));
