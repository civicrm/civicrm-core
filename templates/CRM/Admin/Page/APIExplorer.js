(function($, _, undefined) {
  "use strict";
  /* jshint validthis: true */
  var
    entity,
    action,
    joins = [],
    actions = {values: ['get']},
    fields = [],
    getFieldData = {},
    getFieldsCache = {},
    getActionsCache = {},
    params = {},
    smartyPhp,
    entityDoc,
    fieldTpl = _.template($('#api-param-tpl').html()),
    optionsTpl = _.template($('#api-options-tpl').html()),
    returnTpl = _.template($('#api-return-tpl').html()),
    chainTpl = _.template($('#api-chain-tpl').html()),
    docCodeTpl = _.template($('#doc-code-tpl').html()),
    joinTpl = _.template($('#join-tpl').html()),

    // The following apis do not support the syntax for joins
    // FIXME: the solution is to convert these apis to use _civicrm_api3_basic_get
    NO_JOINS = ['Contact', 'Contribution', 'Pledge', 'Participant'],

    // These types of entityRef don't require any input to open
    // FIXME: ought to be in getfields metadata
    OPEN_IMMEDIATELY = ['RelationshipType', 'Event', 'Group', 'Tag'],

    // Actions that don't support fancy operators
    NO_OPERATORS = ['create', 'update', 'delete', 'setvalue', 'getoptions', 'getactions', 'getfields'],

    // Actions that don't support multiple values
    NO_MULTI = ['delete', 'getoptions', 'getactions', 'getfields',  'getfield', 'setvalue'],

    // Operators with special properties
    BOOL = ['IS NULL', 'IS NOT NULL'],
    TEXT = ['LIKE', 'NOT LIKE'],
    MULTI = ['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'];

  /**
   * Call prettyPrint function and perform additional formatting
   * @param ele
   */
  function prettyPrint(ele) {
    if (typeof window.prettyPrint === 'function') {
      $(ele).removeClass('prettyprinted').addClass('prettyprint');

      window.prettyPrint();

      // Highlight errors in api result
      $('span:contains("error_code"),span:contains("error_message")', '#api-result')
        .siblings('span.str').css('color', '#B00');
    }
  }

  /**
   * Data provider for select2 "field" selectors
   * @returns {{results: Array.<T>}}
   */
  function returnFields() {
    return {results: fields.concat({id: '-', text: ts('Other') + '...', description: ts('Choose a field not in this list')})};
  }

  /**
   * Recursively populates data for select2 "field" selectors
   * @param fields
   * @param entity
   * @param action
   * @param prefix
   * @param required
   */
  function populateFields(fields, entity, action, prefix, required) {
    _.each(getFieldsCache[entity+action].values, function(field) {
      var name = prefix + field.name,
        pos = fields.length;
      fields.push({
        id: name,
        text: field.title || field.name,
        multi: !!field['api.multiple'],
        description: field.description || '',
        required: !(!field['api.required'] || field['api.required'] === '0')
      });
      if (typeof joins[name] === 'string') {
        fields[pos].children = [];
        populateFields(fields[pos].children, joins[name], 'get', name + '.');
      }
      if (!prefix && required && field['api.required'] && field['api.required'] !== '0') {
        required.push(field.name);
      }
    });
  }

  /**
   * Fetch metadata for a field by name - searches across joins
   * @param name string
   * @returns {*}
   */
  function getField(name) {
    var field = {};
    if (name && getFieldData[name]) {
      field = _.cloneDeep(getFieldData[name]);
    } else if (name) {
      var ent = entity,
        act = action,
        prefix = '';
      _.each(name.split('.'), function(piece) {
        if (joins[prefix]) {
          ent = joins[prefix];
          act = 'get';
        }
        name = piece;
        prefix += (prefix.length ? '.' : '') + piece;
      });
      if (getFieldsCache[ent+act].values[name]) {
        field = _.cloneDeep(getFieldsCache[ent+act].values[name]);
      }
    }
    addJoinInfo(field, name);
    return field;
  }

  function addJoinInfo(field, name) {
    if (field.name === 'entity_id') {
      var entityTableParam = name.slice(0, -2) + 'table';
      if (params[entityTableParam]) {
        field.FKApiName = getField(entityTableParam).options[params[entityTableParam]];
      }
    }
  }

  /**
   * Add a "fields" row
   * @param name string
   */
  function addField(name) {
    $('#api-params').append($(fieldTpl({name: name || '', noOps: _.includes(NO_OPERATORS, action)})));
    var $row = $('tr:last-child', '#api-params');
    $('input.api-param-name', $row).crmSelect2({
      data: returnFields,
      formatSelection: function(field) {
        return field.text +
          (field.required ? ' <span class="crm-marker">*</span>' : '');
      },
      formatResult: function(field) {
        return field.text +
          (field.required ? ' <span class="crm-marker">*</span>' : '') +
          '<div class="api-field-desc">' + field.description + '</div>';
      }
    }).change();
  }

  /**
   * Add a new "options" row
   */
  function addOptionField() {
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
        return '<i class="crm-i fa-link"></i> API ' +
          ($(item.element).hasClass('strikethrough') ? '<span class="strikethrough">' + item.text + '</span>' : item.text);
      },
      placeholder: '<i class="crm-i fa-link"></i> ' + ts('Entity'),
      escapeMarkup: function(m) {return m;}
    });
  }

  /**
   * Fetch available actions for selected chained entity
   */
  function getChainedAction() {
    var
      $selector = $(this),
      entity = $selector.val(),
      $row = $selector.closest('tr');
    if (entity) {
      $selector.prop('disabled', true);
      getActions(entity)
        .done(function(actions) {
          $selector.prop('disabled', false);
          CRM.utils.setOptions($('.api-chain-action', $row), _.transform(actions.values, function(ret, item) {ret.push({value: item, key: item});}));
        });
    }
  }

  /**
   * Fetch metadata from the api and cache locally for performance
   * Returns a deferred object which resolves to entity.getfields
   */
  function getMetadata(entity, action) {
    var response = $.Deferred();
    if (getFieldsCache[entity+action]) {
      response.resolve(getFieldsCache[entity+action]);
    } else {
      var apiCalls = {
        getfields: [entity, 'getfields', {
          api_action: action,
          options: {get_options: 'all', get_options_context: 'match'}
        }]
      };
      if (!getActionsCache[entity]) {
        apiCalls.getactions = [entity, 'getactions'];
      }
      CRM.api3(apiCalls)
        .done(function(data) {
          data.getfields.values = _.indexBy(data.getfields.values, 'name');
          getFieldsCache[entity+action] = data.getfields;
          getActionsCache[entity] = getActionsCache[entity] || data.getactions;
          response.resolve(getFieldsCache[entity+action]);
        });
    }
    return response;
  }

  /**
   * TODO: This works given the current code structure but would cause race conditions if called many times per second
   * @param entity string
   * @returns $.Deferred
   */
  function getActions(entity) {
    if (getActionsCache[entity]) {
      return $.Deferred().resolve(getActionsCache[entity]);
    } else {
      return CRM.api3(entity, 'getactions');
    }
  }

  /**
   * Respond to changing the main entity+action
   */
  function onChangeEntityOrAction(changedElement) {
    var required = [];
    fields = [];
    joins = [];
    getFieldData = {};
    // Special case for getfields
    if (action === 'getfields') {
      fields.push({
        id: 'api_action',
        text: ts('Action')
      });
      getFieldData.api_action = {
        name: 'api_action',
        options: _.reduce(actions.values, function(ret, item) {
          ret[item] = item;
          return ret;
        }, {})
      };
      getFieldsCache[entity+action] = {values: _.cloneDeep(getFieldData)};
      showFields(['api_action']);
      renderJoinSelector();
      return;
    }
    getMetadata(entity, action).done(function(data) {
      if ($(changedElement).is('#api-entity')) {
        actions = getActionsCache[entity];
        populateActions();
        if (data.deprecated) CRM.alert(data.deprecated, entity + ' Deprecated');
      }
      onChangeAction(action);
      getFieldData = data.values;
      populateFields(fields, entity, action, '', required);
      showFields(required);
      renderJoinSelector();
      if (_.includes(['get', 'getsingle', 'getvalue', 'getstat'], action)) {
        showReturn();
      }
    });
  }

  function changeFKEntity() {
    var $row = $(this).closest('tr'),
      name = $('input.api-param-name', $row).val(),
      operator = $('.api-param-op', $row).val();
    if (name && name.slice(-12) === 'entity_table') {
      $('input[value=' + name.slice(0, -5) + 'id]', '#api-join').prop('checked', false).change();
    }
  }

  /**
   * For "get" actions show the "return" options
   *
   * TODO: Too many hard-coded actions here. Need a way to fetch this from metadata
   */
  function showReturn() {
    var title = ts('Fields to return'),
      params = {
        data: returnFields,
        multiple: true,
        placeholder: ts('Leave blank for default'),
        formatResult: function(field) {
          return field.text + '<div class="api-field-desc">' + field.description + '</div>';
        }
      };
    if (action == 'getstat') {
      title = ts('Group by');
    }
    if (action == 'getvalue') {
      title = ts('Return Value');
      params.placeholder = ts('Select field');
      params.multiple = false;
    }
    $('#api-params').prepend($(returnTpl({title: title, required: action == 'getvalue'})));
    $('#api-return-value').crmSelect2(params);
  }

  /**
   * Test whether an action is deprecated
   * @param action
   * @returns {boolean}
   */
  function isActionDeprecated(action) {
    return !!(typeof actions.deprecated === 'object' && actions.deprecated[action]);
  }

  /**
   * Render action text depending on deprecation status
   * @param option
   * @returns {string}
   */
  function renderAction(option) {
    return isActionDeprecated(option.id) ? '<span class="strikethrough">' + option.text + '</span>' : option.text;
  }

  /**
   * Called after getActions to populate action list
   */
  function populateActions() {
    var val = $('#api-action').val();
    $('#api-action').removeClass('loading').select2({
      data: _.transform(actions.values, function(ret, item) {ret.push({text: item, id: item});}),
      formatSelection: renderAction,
      formatResult: renderAction
    });
    // If previously selected action is not available, set it to 'get' if possible
    if (!_.includes(actions.values, val)) {
      $('#api-action').select2('val', !_.includes(actions.values, 'get') ? actions.values[0] : 'get', true);
    }
  }

  /**
   * Check for and display action-specific deprecation notices
   * @param action
   */
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

  function isYesNo(fieldName) {
    return getField(fieldName).type === 16;
  }

  /**
   * Should we render a select or textfield?
   *
   * @param fieldName
   * @param operator
   * @returns boolean
   */
  function isSelect(fieldName, operator) {
    var fieldSpec = getField(fieldName);
    return (isYesNo(fieldName) || fieldSpec.options || fieldSpec.FKApiName) && !_.includes(TEXT, operator);
  }

  /**
   * Should we render a select as single or multi?
   *
   * @param fieldName
   * @param operator
   * @returns boolean
   */
  function isMultiSelect(fieldName, operator) {
    if (isYesNo(fieldName) || _.includes(NO_MULTI, action)) {
      return false;
    }
    if (_.includes(MULTI, operator)) {
      return true;
    }
    // The = operator is ambiguous but all others can be safely assumed to be single
    if (operator !== '=') {
      return false;
    }
    return fieldName !== 'entity_table';
    /*
     * Attempt to resolve the ambiguity of the = operator using metadata
     * commented out because there is not enough metadata in the api at this time
     * to accurately figure it out.
     */
    // var field = fieldName && _.find(fields, 'id', fieldName);
    // return field && field.multi;
  }

  /**
   * Render value input as a textfield, option list, entityRef, or hidden,
   * Depending on selected param name and operator
   */
  function renderValueField() {
    var $row = $(this).closest('tr'),
      name = $('input.api-param-name', $row).val(),
      operator = $('.api-param-op', $row).val(),
      $valField = $('input.api-param-value', $row),
      multiSelect = isMultiSelect(name, operator),
      currentVal = $valField.val(),
      fieldSpec = getField(name),
      wasSelect = $valField.data('select2');
    if (wasSelect) {
      $valField.crmEntityRef('destroy');
    }
    $valField.attr('placeholder', ts('Value'));
    // Boolean fields only have 1 possible value
    if (_.includes(BOOL, operator)) {
      $valField.css('visibility', 'hidden').val('1');
      return;
    }
    $valField.css('visibility', '');
    // Option list or entityRef input
    if (isSelect(name, operator)) {
      $valField.attr('placeholder', ts('- select -'));
      // Reset value before switching to a select from something else
      if ($(this).is('.api-param-name') || !wasSelect) {
        $valField.val('');
      }
      // When switching from multi-select to single select
      else if (!multiSelect && _.includes(currentVal, ',')) {
        $valField.val(currentVal.split(',')[0]);
      }
      // Yes-No options
      if (isYesNo(name)) {
        $valField.select2({
          data: [{id: 1, text: ts('Yes')}, {id: 0, text: ts('No')}]
        });
      }
      // Select options
      else if (fieldSpec.options) {
        $valField.select2({
          multiple: multiSelect,
          data: _.map(fieldSpec.options, function (value, key) {
            return {id: key, text: value};
          })
        });
      }
      // EntityRef
      else {
        var entity = fieldSpec.FKApiName;
        $valField.attr('placeholder', entity == 'Contact' ? '[' + ts('Auto-Select Current User') + ']' : ts('- select -'));
        $valField.crmEntityRef({
          entity: entity,
          select: {
            multiple: multiSelect,
            minimumInputLength: _.includes(OPEN_IMMEDIATELY, entity) ? 0 : 1,
            // If user types a numeric id, allow it as a choice
            createSearchChoice: function(input) {
              var match = /[1-9][0-9]*/.exec(input);
              if (match && match[0] === input) {
                return {id: input, label: input};
              }
            }
          }
        });
      }
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
        /* jshint evil: true */
        return eval(val);
      }
      // Quoted strings
      if ((first === '"' || first === "'") && last === first) {
        return val.slice(1, -1);
      }
      // Parse json - use eval rather than $.parseJSON because it's less strict about formatting
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
      // Integers - skip any multidigit number that starts with 0 to avoid oddities (it will be treated as a string below)
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
   * TODO: Use short array syntax when we drop support for php 5.3
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
   * @param value string
   * @param js string
   * @param key string
   */
  function smartyFormat(value, js, key) {
    var varName = 'param_' + key.replace(/[. -]/g, '_').toLowerCase();
    // Can't pass array literals directly into smarty so we add a php snippet
    if (_.includes(js, '[') || _.includes(js, '{')) {
      smartyPhp.push('$this->assign("'+ varName + '", '+ phpFormat(value) +');');
      return '$' + varName;
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
        input = $(this).val(),
        op = $('select.api-param-op', $row).val() || '=',
        name = $('input.api-param-name', $row).val(),
        // Workaround for ambiguity of the = operator
        makeArray = (op === '=' && isSelect(name, op)) ? _.includes(input, ',') : op !== '=' && isMultiSelect(name, op),
        val = evaluate(input, makeArray);

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
      // Default for contact ref fields
      if ($(this).is('.crm-contact-ref') && input === '') {
        val = evaluate('user_contact_id', makeArray);
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

  /**
   * Display error message on incorrectly-formatted params
   * @param el
   */
  function setError(el) {
    if (!$(el).hasClass('crm-error')) {
      var msg = ts('Syntax error: input should be valid JSON or a quoted string.');
      $(el)
        .addClass('crm-error')
        .css('width', '82%')
        .attr('title', msg)
        .before('<i class="crm-i fa-exclamation-triangle crm-i-red" title="'+msg+'"></i> ')
        .tooltip();
    }
  }

  /**
   * Remove error message
   * @param el
   */
  function clearError(el) {
    $(el)
      .removeClass('crm-error')
      .attr('title', '')
      .css('width', '85%')
      .tooltip('destroy')
      .siblings('.fa-exclamation-triangle').remove();
  }

  /**
   * Render the api request in various formats
   */
  function formatQuery() {
    var i = 0, q = {
      smarty: "{crmAPI var='result' entity='" + entity + "' action='" + action + "'" + (params.sequential ? '' : ' sequential=0'),
      php: "$result = civicrm_api3('" + entity + "', '" + action + "'",
      json: "CRM.api3('" + entity + "', '" + action + "'",
      drush: "drush cvapi " + entity + '.' + action + ' ',
      wpcli: "wp cv api " + entity + '.' + action + ' ',
      rest: CRM.config.resourceBase + "extern/rest.php?entity=" + entity + "&action=" + action + "&api_key=userkey&key=sitekey&json=" + JSON.stringify(params)
    };
    smartyPhp = [];
    $.each(params, function(key, value) {
      var json = JSON.stringify(value),
        // Encourage 'return' to be an array - at least in php & js
        js = key === 'return' && action !== 'getvalue' ? JSON.stringify(evaluate(value, true)) : json,
        php = key === 'return' && action !== 'getvalue' ? phpFormat(evaluate(value, true)) : phpFormat(value);
      if (!(i++)) {
        q.php += ", array(\n";
        q.json += ", {\n";
      } else {
        q.json += ",\n";
      }
      q.php += "  '" + key + "' => " + php + ",\n";
      q.json += "  \"" + key + '": ' + js;
      // smarty already defaults to sequential
      if (key !== 'sequential') {
        q.smarty += ' ' + key + '=' + smartyFormat(value, json, key);
      }
      // FIXME: This is not totally correct cli syntax
      q.drush += key + '=' + json + ' ';
      q.wpcli += key + '=' + json + ' ';
    });
    if (i) {
      q.php += ")";
      q.json += "\n}";
    }
    q.php += ");";
    q.json += ").done(function(result) {\n  // do something\n});";
    q.smarty += "}\n{foreach from=$result.values item=" + entity.toLowerCase() + "}\n  {$" + entity.toLowerCase() + ".some_field}\n{/foreach}";
    if (!_.includes(action, 'get')) {
      q.smarty = '{* Smarty API only works with get actions *}';
    } else if (smartyPhp.length) {
      q.smarty = "{php}\n  " + smartyPhp.join("\n  ") + "\n{/php}\n" + q.smarty;
    }
    $.each(q, function(type, val) {
      $('#api-' + type).text(val);
    });
    prettyPrint('#api-generated pre');
  }

  /**
   * Submit button handler
   * @param e
   */
  function submit(e) {
    e.preventDefault();
    if (!entity || !action) {
      alert(ts('Select an entity.'));
      return;
    }
    if (!_.includes(action, 'get') && action != 'check') {
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
    var footer;
    $('#api-result').html('<div class="crm-loading-element"></div>');
    $.ajax({
      url: CRM.url('civicrm/ajax/rest'),
      data: {
        entity: entity,
        action: action,
        prettyprint: 1,
        json: JSON.stringify(params)
      },
      type: _.includes(action, 'get') ? 'GET' : 'POST',
      dataType: 'text'
    }).done(function(text) {
      // There may be debug information appended to the end of the json string
      var footerPos = text.indexOf("\n}<");
      if (footerPos) {
        footer = text.substr(footerPos + 2);
        text = text.substr(0, footerPos + 2);
      }
      $('#api-result').text(text);
      prettyPrint('#api-result');
      if (footer) {
        $('#api-result').append(footer);
      }
    });
  }

  /**
   * Fetch list of example files for a given entity
   */
  function getExamples() {
    CRM.utils.setOptions($('#example-action').prop('disabled', true).addClass('loading'), []);
    $.getJSON(CRM.url('civicrm/ajax/apiexample', {entity: $(this).val()}))
      .done(function(result) {
        CRM.utils.setOptions($('#example-action').prop('disabled', false).removeClass('loading'), result);
      });
  }

  /**
   * Fetch and display an example file
   */
  function getExample() {
    var
      entity = $('#example-entity').val(),
      action = $('#example-action').val();
    if (entity && action) {
      $('#example-result').block();
      $.get(CRM.url('civicrm/ajax/apiexample', {file: entity + '/' + action}))
        .done(function(result) {
          $('#example-result').unblock().text(result);
          prettyPrint('#example-result');
        });
    } else {
      $('#example-result').text($('#example-result').attr('placeholder'));
    }
  }

  /**
   * Fetch entity docs & actions
   */
  function getDocEntity() {
    CRM.utils.setOptions($('#doc-action').prop('disabled', true).addClass('loading'), []);
    $.getJSON(CRM.url('civicrm/ajax/apidoc', {entity: $(this).val()}))
      .done(function(result) {
        entityDoc = result.doc;
        CRM.utils.setOptions($('#doc-action').prop('disabled', false).removeClass('loading'), result.actions);
        $('#doc-result').html(result.doc);
        prettyPrint('#doc-result pre');
      });
  }

  /**
   * Fetch entity+action docs & code
   */
  function getDocAction() {
    var
      entity = $('#doc-entity').val(),
      action = $('#doc-action').val();
    if (entity && action) {
      $('#doc-result').block();
      $.get(CRM.url('civicrm/ajax/apidoc', {entity: entity, action: action}))
        .done(function(result) {
          $('#doc-result').unblock().html(result.doc);
          if (result.code) {
            $('#doc-result').append(docCodeTpl(result));
          }
          prettyPrint('#doc-result pre');
        });
    } else {
      $('#doc-result').html(entityDoc);
      prettyPrint('#doc-result pre');
    }
    checkBookKeepingEntity(entity, action);
  }

  /**
   * Check if entity is Financial Trxn and Entity Financial Trxn
   * and Action is Create, delete, update etc then display warning
   */
  function checkBookKeepingEntity(entity, action) {
    if ($.inArray(entity, ['EntityFinancialTrxn', 'FinancialTrxn']) > -1 && $.inArray(action, ['delete', 'setvalue', 'replace', 'create']) > -1) {
      var msg = ts('Given the importance of auditability, extension developers are strongly discouraged from writing code to add, update or delete entries in the civicrm_financial_item, civicrm_entity_financial_trxn, and civicrm_financial_trxn tables. Before publishing an extension on civicrm.org that does any of this, please ask for a special bookkeeping code review for the extension.');
      CRM.alert(msg, 'warning');
    }
  }

  /**
   * Renders nested checkboxes for adding joins to an api.get call
   */
  function renderJoinSelector() {
    $('#api-join').hide();
    if (!_.includes(NO_JOINS, entity) && _.includes(['get', 'getsingle'], action)) {
      var joinable = {};
      (function recurse(fields, joinable, prefix, depth, entities) {
        _.each(fields, function(field) {
          var name = prefix + field.name;
          addJoinInfo(field, name);
          var entity = field.FKApiName;
          if (entity) {
            joinable[name] = {
              title: field.title + ' (' + field.FKApiName + ')',
              entity: entity,
              checked: !!joins[name]
            };
            // Expose further joins if we are not over the limit or recursing onto the same entity multiple times
            if (joins[name] && depth < CRM.vars.explorer.max_joins && !_.countBy(entities)[entity]) {
              joinable[name].children = {};
              recurse(getFieldsCache[entity+'get'].values, joinable[name].children, name + '.', depth+1, entities.concat(entity));
            }
          } else if (field.name == 'entity_id' && fields.entity_table && fields.entity_table.options) {
            joinable[name] = {
              title: field.title + ' (' + ts('First select %1', {1: fields.entity_table.title}) + ')',
              entity: '',
              disabled: true
            };
          }
        });
      })(_.cloneDeep(getFieldData), joinable, '', 1, [entity]);
      if (!_.isEmpty(joinable)) {
        // Send joinTpl as a param so it can recursively call itself to render children
        $('#api-join').show().children('div').html(joinTpl({joins: joinable, tpl: joinTpl}));
      }
    }
  }

  /**
   * When adding or removing a join from an api.get call
   */
  function onSelectJoin() {
    var name = $(this).val(),
      ent = $(this).data('entity');
    fields = [];
    $('input', '#api-join').prop('disabled', true);
    if ($(this).is(':checked')) {
      joins[name] = ent;
      $('input.api-param-name, #api-return-value').addClass('loading');
      getMetadata(ent, 'get').done(function() {
        renderJoinSelector();
        populateFields(fields, entity, action, '');
        $('input.api-param-name, #api-return-value').removeClass('loading');
      });
    } else {
      joins = _.omit(joins, function(entity, n) {
        return n.indexOf(name) === 0;
      });
      renderJoinSelector();
      populateFields(fields, entity, action, '');
    }
  }

  $(document).ready(function() {
    // Set up tabs - bind active tab to document hash because... it's cool?
    document.location.hash = document.location.hash || 'explorer';
      $('#mainTabContainer')
      .tabs({
          active: $(document.location.hash + '-tab').index() - 1
        })
      .on('tabsactivate', function(e, ui) {
        if (ui.newPanel) {
          document.location.hash = ui.newPanel.attr('id').replace('-tab', '');
        }
      });
    $(window).on('hashchange', function() {
      $('#mainTabContainer').tabs('option', 'active', $(document.location.hash + '-tab').index() - 1);
    });

    // Initialize widgets
    $('#api-entity, #example-entity, #doc-entity').crmSelect2({
      // Add strikethough class to selection to indicate deprecated apis
      formatSelection: function(option) {
        return $(option.element).hasClass('strikethrough') ? '<span class="strikethrough">' + option.text + '</span>' : option.text;
      }
    });
    $('form#api-explorer')
      .on('change', '#api-entity, #api-action', function() {
        entity = $('#api-entity').val();
        action = $('#api-action').val();
        joins = {};
        if ($(this).is('#api-entity')) {
          $('#api-action').addClass('loading');
        }
        $('#api-params').html('<tr><td colspan="4" class="crm-loading-element"></td></tr>');
        $('#api-params-table thead').show();
        onChangeEntityOrAction(this);
        buildParams();
        checkBookKeepingEntity(entity, action);
      })
      .on('change keyup', 'input.api-input, #api-params select', buildParams)
      .on('change', '.api-param-name, .api-param-value, .api-param-op', changeFKEntity)
      .on('submit', submit);

    $('#api-params')
      .on('change', 'input.api-param-name, select.api-param-op', renderValueField)
      .on('select2-selecting', 'input.api-param-name, .api-option-name, #api-return-value', function(e) {
        if (e.val === '-') {
          $(this).one('change', function() {
            $(this)
              .crmSelect2('destroy')
              .val('')
              .focus();
          });
        }
      })
      .on('click', '.api-param-remove', function(e) {
        e.preventDefault();
        $(this).closest('tr').remove();
        buildParams();
      })
      .on('change', 'select.api-chain-entity', getChainedAction);
    $('#api-join').on('change', 'input', onSelectJoin);
    $('#example-entity').on('change', getExamples);
    $('#example-action').on('change', getExample);
    $('#doc-entity').on('change', getDocEntity);
    $('#doc-action').on('change', getDocAction);
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
    populateActions();
  });
}(CRM.$, CRM._));
