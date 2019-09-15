(function(angular, $, _, undefined) {

  // Schema metadata
  var schema = CRM.vars.api4.schema;
  // FK schema data
  var links = CRM.vars.api4.links;
  // Cache list of entities
  var entities = [];
  // Cache list of actions
  var actions = [];
  // Field options
  var fieldOptions = {};


  angular.module('api4Explorer').config(function($routeProvider) {
    $routeProvider.when('/explorer/:api4entity?/:api4action?', {
      controller: 'Api4Explorer',
      templateUrl: '~/api4Explorer/Explorer.html',
      reloadOnSearch: false
    });
  });

  angular.module('api4Explorer').controller('Api4Explorer', function($scope, $routeParams, $location, $timeout, $http, crmUiHelp, crmApi4) {
    var ts = $scope.ts = CRM.ts('api4');
    $scope.entities = entities;
    $scope.actions = actions;
    $scope.fields = [];
    $scope.fieldsAndJoins = [];
    $scope.availableParams = {};
    $scope.params = {};
    $scope.index = '';
    var getMetaParams = {},
      objectParams = {orderBy: 'ASC', values: '', chain: ['Entity', '', '{}']},
      helpTitle = '',
      helpContent = {};
    $scope.helpTitle = '';
    $scope.helpContent = {};
    $scope.entity = $routeParams.api4entity;
    $scope.result = [];
    $scope.status = 'default';
    $scope.loading = false;
    $scope.controls = {};
    $scope.code = {
      php: '',
      javascript: '',
      cli: ''
    };

    if (!entities.length) {
      formatForSelect2(schema, entities, 'name', ['description']);
    }

    $scope.$bindToRoute({
      expr: 'index',
      param: 'index',
      default: ''
    });

    function ucfirst(str) {
      return str[0].toUpperCase() + str.slice(1);
    }

    function lcfirst(str) {
      return str[0].toLowerCase() + str.slice(1);
    }

    function pluralize(str) {
      switch (str[str.length-1]) {
        case 's':
          return str + 'es';
        case 'y':
          return str.slice(0, -1) + 'ies';
        default:
          return str + 's';
      }
    }

    // Turn a flat array into a select2 array
    function arrayToSelect2(array) {
      var out = [];
      _.each(array, function(item) {
        out.push({id: item, text: item});
      });
      return out;
    }

    // Reformat an existing array of objects for compatibility with select2
    function formatForSelect2(input, container, key, extra, prefix) {
      _.each(input, function(item) {
        var id = (prefix || '') + item[key];
        var formatted = {id: id, text: id};
        if (extra) {
          _.merge(formatted, _.pick(item, extra));
        }
        container.push(formatted);
      });
      return container;
    }

    function getFieldList(source) {
      var fields = [],
        fieldInfo = _.findWhere(getEntity().actions, {name: $scope.action}).fields;
      formatForSelect2(fieldInfo, fields, 'name', ['description', 'required', 'default_value']);
      return fields;
    }

    function addJoins(fieldList) {
      var fields = _.cloneDeep(fieldList),
        fks = _.findWhere(links, {entity: $scope.entity}) || {};
      _.each(fks.links, function(link) {
        var linkFields = entityFields(link.entity);
        if (linkFields) {
          fields.push({
            text: link.alias,
            description: 'Join to ' + link.entity,
            children: formatForSelect2(linkFields, [], 'name', ['description'], link.alias + '.')
          });
        }
      });
      return fields;
    }

    $scope.help = function(title, param) {
      if (!param) {
        $scope.helpTitle = helpTitle;
        $scope.helpContent = helpContent;
      } else {
        $scope.helpTitle = title;
        $scope.helpContent = param;
      }
    };

    $scope.fieldHelp = function(fieldName) {
      var field = getField(fieldName, $scope.entity, $scope.action);
      if (!field) {
        return;
      }
      var info = {
          description: field.description,
          type: field.data_type
        };
      if (field.default_value) {
        info.default = field.default_value;
      }
      if (field.required_if) {
        info.required_if = field.required_if;
      } else if (field.required) {
        info.required = 'true';
      }
      return info;
    };

    $scope.valuesFields = function() {
      var fields = _.cloneDeep($scope.fields);
      // Disable fields that are already in use
      _.each($scope.params.values || [], function(val) {
        (_.findWhere(fields, {id: val[0]}) || {}).disabled = true;
      });
      return {results: fields};
    };

    $scope.formatSelect2Item = function(row) {
      return _.escape(row.text) +
        (row.required ? '<span class="crm-marker"> *</span>' : '') +
        (row.description ? '<div class="crm-select2-row-description"><p>' + _.escape(row.description) + '</p></div>' : '');
    };

    $scope.clearParam = function(name) {
      $scope.params[name] = $scope.availableParams[name].default;
    };

    $scope.isSpecial = function(name) {
      var specialParams = ['select', 'fields', 'action', 'where', 'values', 'orderBy', 'chain'];
      return _.contains(specialParams, name);
    };

    $scope.selectRowCount = function() {
      if ($scope.isSelectRowCount()) {
        $scope.params.select = [];
      } else {
        $scope.params.select = ['row_count'];
        if ($scope.params.limit == 25) {
          $scope.params.limit = 0;
        }
      }
    };

    $scope.isSelectRowCount = function() {
      return $scope.params && $scope.params.select && $scope.params.select.length === 1 && $scope.params.select[0] === 'row_count';
    };

    function getEntity(entityName) {
      return _.findWhere(schema, {name: entityName || $scope.entity});
    }

    // Get all params that have been set
    function getParams() {
      var params = {};
      _.each($scope.params, function(param, key) {
        if (param != $scope.availableParams[key].default && !(typeof param === 'object' && _.isEmpty(param))) {
          if (_.contains($scope.availableParams[key].type, 'array') && (typeof objectParams[key] === 'undefined')) {
            params[key] = parseYaml(_.cloneDeep(param));
          } else {
            params[key] = param;
          }
        }
      });
      _.each(objectParams, function(defaultVal, key) {
        if (params[key]) {
          var newParam = {};
          _.each(params[key], function(item) {
            newParam[item[0]] = parseYaml(_.cloneDeep(item[1]));
          });
          params[key] = newParam;
        }
      });
      return params;
    }

    function parseYaml(input) {
      if (typeof input === 'undefined') {
        return undefined;
      }
      if (_.isObject(input) || _.isArray(input)) {
        _.each(input, function(item, index) {
          input[index] = parseYaml(item);
        });
        return input;
      }
      try {
        var output = (input === '>') ? '>' : jsyaml.safeLoad(input);
        // We don't want dates parsed to js objects
        return _.isDate(output) ? input : output;
      } catch (e) {
        return input;
      }
    }

    function selectAction() {
      $scope.action = $routeParams.api4action;
      $scope.fieldsAndJoins = [];
      if (!actions.length) {
        formatForSelect2(getEntity().actions, actions, 'name', ['description', 'params']);
      }
      if ($scope.action) {
        var actionInfo = _.findWhere(actions, {id: $scope.action});
        $scope.fields = getFieldList();
        if (_.contains(['get', 'update', 'delete', 'replace'], $scope.action)) {
          $scope.fieldsAndJoins = addJoins($scope.fields);
        } else {
          $scope.fieldsAndJoins = $scope.fields;
        }
        _.each(actionInfo.params, function (param, name) {
          var format,
            defaultVal = _.cloneDeep(param.default);
          if (param.type) {
            switch (param.type[0]) {
              case 'int':
              case 'bool':
                format = param.type[0];
                break;

              case 'array':
              case 'object':
                format = 'json';
                break;

              default:
                format = 'raw';
            }
            if (name == 'limit') {
              defaultVal = 25;
            }
            if (name === 'values') {
              defaultVal = defaultValues(defaultVal);
            }
            $scope.$bindToRoute({
              expr: 'params["' + name + '"]',
              param: name,
              format: format,
              default: defaultVal,
              deep: format === 'json'
            });
          }
          if (typeof objectParams[name] !== 'undefined') {
            $scope.$watch('params.' + name, function(values) {
              // Remove empty values
              _.each(values, function(clause, index) {
                if (!clause || !clause[0]) {
                  $scope.params[name].splice(index, 1);
                }
              });
            }, true);
            $scope.$watch('controls.' + name, function(value) {
              var field = value;
              $timeout(function() {
                if (field) {
                  var defaultOp = _.cloneDeep(objectParams[name]);
                  if (name === 'chain') {
                    var num = $scope.params.chain.length;
                    defaultOp[0] = field;
                    field = 'name_me_' + num;
                  }
                  $scope.params[name].push([field, defaultOp]);
                  $scope.controls[name] = null;
                }
              });
            });
          }
        });
        $scope.availableParams = actionInfo.params;
      }
      writeCode();
    }

    function defaultValues(defaultVal) {
      _.each($scope.fields, function(field) {
        if (field.required) {
          defaultVal.push([field.id, '']);
        }
      });
      return defaultVal;
    }

    function stringify(value, trim) {
      if (typeof value === 'undefined') {
        return '';
      }
      var str = JSON.stringify(value).replace(/,/g, ', ');
      if (trim) {
        str = str.slice(1, -1);
      }
      return str.trim();
    }

    function writeCode() {
      var code = {
        php: ts('Select an entity and action'),
        javascript: '',
        cli: ''
      },
        entity = $scope.entity,
        action = $scope.action,
        params = getParams(),
        index = isInt($scope.index) ? +$scope.index : $scope.index,
        result = 'result';
      if ($scope.entity && $scope.action) {
        if (action.slice(0, 3) === 'get') {
          result = entity.substr(0, 7) === 'Custom_' ? _.camelCase(entity.substr(7)) : entity;
          result = lcfirst(action.replace(/s$/, '').slice(3) || result);
        }
        var results = lcfirst(_.isNumber(index) ? result : pluralize(result)),
          paramCount = _.size(params),
          isSelectRowCount = params.select && params.select.length === 1 && params.select[0] === 'row_count',
          i = 0;

        if (isSelectRowCount) {
          results = result + 'Count';
        }

        // Write javascript
        code.javascript = "CRM.api4('" + entity + "', '" + action + "', {";
        _.each(params, function(param, key) {
          code.javascript += "\n  " + key + ': ' + stringify(param) +
            (++i < paramCount ? ',' : '');
          if (key === 'checkPermissions') {
            code.javascript += ' // IGNORED: permissions are always enforced from client-side requests';
          }
        });
        code.javascript += "\n}";
        if (index || index === 0) {
          code.javascript += ', ' + JSON.stringify(index);
        }
        code.javascript += ").then(function(" + results + ") {\n  // do something with " + results + " array\n}, function(failure) {\n  // handle failure\n});";

        // Write php code
        if (entity.substr(0, 7) !== 'Custom_') {
          code.php = '$' + results + " = \\Civi\\Api4\\" + entity + '::' + action + '()';
        } else {
          code.php = '$' + results + " = \\Civi\\Api4\\CustomValue::" + action + "('" + entity.substr(7) + "')";
        }
        _.each(params, function(param, key) {
          var val = '';
          if (typeof objectParams[key] !== 'undefined' && key !== 'chain') {
            _.each(param, function(item, index) {
              val = phpFormat(index) + ', ' + phpFormat(item, 4);
              code.php += "\n  ->add" + ucfirst(key).replace(/s$/, '') + '(' + val + ')';
            });
          } else if (key === 'where') {
            _.each(param, function (clause) {
              if (clause[0] === 'AND' || clause[0] === 'OR' || clause[0] === 'NOT') {
                code.php += "\n  ->addClause(" + phpFormat(clause[0]) + ", " + phpFormat(clause[1]).slice(1, -1) + ')';
              } else {
                code.php += "\n  ->addWhere(" + phpFormat(clause).slice(1, -1) + ")";
              }
            });
          } else if (key === 'select' && isSelectRowCount) {
            code.php += "\n  ->selectRowCount()";
          } else {
            code.php += "\n  ->set" + ucfirst(key) + '(' + phpFormat(param, 4) + ')';
          }
        });
        code.php += "\n  ->execute()";
        if (_.isNumber(index)) {
          code.php += !index ? '\n  ->first()' : (index === -1 ? '\n  ->last()' : '\n  ->itemAt(' + index + ')');
        } else if (index) {
          code.php += "\n  ->indexBy('" + index + "')";
        } else if (isSelectRowCount) {
          code.php += "\n  ->count()";
        }
        code.php += ";\n";
        if (!_.isNumber(index) && !isSelectRowCount) {
          code.php += "foreach ($" + results + ' as $' + ((_.isString(index) && index) ? index + ' => $' : '') + result + ') {\n  // do something\n}';
        }

        // Write cli code
        code.cli = 'cv api4 ' + entity + '.' + action + " '" + stringify(params) + "'";
      }
      _.each(code, function(val, type) {
        $scope.code[type] = prettyPrintOne(val);
      });
    }

    function isInt(value) {
      if (_.isFinite(value)) {
        return true;
      }
      if (!_.isString(value)) {
        return false;
      }
      return /^-{0,1}\d+$/.test(value);
    }

    function formatMeta(resp) {
      var ret = '';
      _.each(resp, function(val, key) {
        if (key !== 'values' && !_.isPlainObject(val) && !_.isFunction(val)) {
          ret += (ret.length ? ', ' : '') + key + ': ' + (_.isArray(val) ? '[' + val + ']' : val);
        }
      });
      return prettyPrintOne(ret);
    }

    $scope.execute = function() {
      $scope.status = 'warning';
      $scope.loading = true;
      $http.get(CRM.url('civicrm/ajax/api4/' + $scope.entity + '/' + $scope.action, {
        params: angular.toJson(getParams()),
        index: $scope.index
      })).then(function(resp) {
          $scope.loading = false;
          $scope.status = 'success';
          $scope.result = [formatMeta(resp.data), prettyPrintOne(JSON.stringify(resp.data.values, null, 2), 'js', 1)];
        }, function(resp) {
          $scope.loading = false;
          $scope.status = 'danger';
          $scope.result = [formatMeta(resp), prettyPrintOne(JSON.stringify(resp.data, null, 2))];
        });
    };

    /**
     * Format value to look like php code
     */
    function phpFormat(val, indent) {
      if (typeof val === 'undefined') {
        return '';
      }
      indent = (typeof indent === 'number') ? _.repeat(' ', indent) : (indent || '');
      var ret = '',
        baseLine = indent ? indent.slice(0, -2) : '',
        newLine = indent ? '\n' : '';
      if ($.isPlainObject(val)) {
        $.each(val, function(k, v) {
          ret += (ret ? ', ' : '') + newLine + indent + "'" + k + "' => " + phpFormat(v);
        });
        return '[' + ret + newLine + baseLine + ']';
      }
      if ($.isArray(val)) {
        $.each(val, function(k, v) {
          ret += (ret ? ', ' : '') + newLine + indent + phpFormat(v);
        });
        return '[' + ret + newLine + baseLine + ']';
      }
      if (_.isString(val) && !_.contains(val, "'")) {
        return "'" + val + "'";
      }
      return JSON.stringify(val).replace(/\$/g, '\\$');
    }

    function fetchMeta() {
      crmApi4(getMetaParams)
        .then(function(data) {
          if (data.actions) {
            getEntity().actions = data.actions;
            selectAction();
          }
        });
    }

    // Help for an entity with no action selected
    function showEntityHelp(entityName) {
      var entityInfo = getEntity(entityName);
      $scope.helpTitle = helpTitle = $scope.entity;
      $scope.helpContent = helpContent = {
        description: entityInfo.description,
        comment: entityInfo.comment
      };
    }

    if (!$scope.entity) {
      $scope.helpTitle = helpTitle = ts('Help');
      $scope.helpContent = helpContent = {description: ts('Welcome to the api explorer.'), comment: ts('Select an entity to begin.')};
    } else if (!actions.length && !getEntity().actions) {
      getMetaParams.actions = [$scope.entity, 'getActions', {chain: {fields: [$scope.entity, 'getFields', {action: '$name'}]}}];
      fetchMeta();
    } else {
      selectAction();
    }

    if ($scope.entity) {
      showEntityHelp($scope.entity);
    }

    // Update route when changing entity
    $scope.$watch('entity', function(newVal, oldVal) {
      if (oldVal !== newVal) {
        // Flush actions cache to re-fetch for new entity
        actions = [];
        $location.url('/explorer/' + newVal);
      }
    });

    // Update route when changing actions
    $scope.$watch('action', function(newVal, oldVal) {
      if ($scope.entity && $routeParams.api4action !== newVal && !_.isUndefined(newVal)) {
        $location.url('/explorer/' + $scope.entity + '/' + newVal);
      } else if (newVal) {
        $scope.helpTitle = helpTitle = $scope.entity + '::' + newVal;
        $scope.helpContent = helpContent = _.pick(_.findWhere(getEntity().actions, {name: newVal}), ['description', 'comment']);
      }
    });

    $scope.indexHelp = {
      description: ts('(string|int) Index results or select by index.'),
      comment: ts('Pass a string to index the results by a field value. E.g. index: "name" will return an associative array with names as keys.') + '\n\n' +
        ts('Pass an integer to return a single result; e.g. index: 0 will return the first result, 1 will return the second, and -1 will return the last.')
    };

    $scope.$watch('params', writeCode, true);
    $scope.$watch('index', writeCode);
    writeCode();

  });

  angular.module('api4Explorer').directive('crmApi4WhereClause', function($timeout) {
    return {
      scope: {
        data: '=crmApi4WhereClause'
      },
      templateUrl: '~/api4Explorer/WhereClause.html',
      link: function (scope, element, attrs) {
        var ts = scope.ts = CRM.ts('api4');
        scope.newClause = '';
        scope.conjunctions = ['AND', 'OR', 'NOT'];
        scope.operators = CRM.vars.api4.operators;

        scope.addGroup = function(op) {
          scope.data.where.push([op, []]);
        };

        scope.removeGroup = function() {
          scope.data.groupParent.splice(scope.data.groupIndex, 1);
        };

        scope.onSort = function(event, ui) {
          $('.api4-where-fieldset').toggleClass('api4-sorting', event.type === 'sortstart');
          $('.api4-input.form-inline').css('margin-left', '');
        };

        // Indent clause while dragging between nested groups
        scope.onSortOver = function(event, ui) {
          var offset = 0;
          if (ui.sender) {
            offset = $(ui.placeholder).offset().left - $(ui.sender).offset().left;
          }
          $('.api4-input.form-inline.ui-sortable-helper').css('margin-left', '' + offset + 'px');
        };

        scope.$watch('newClause', function(value) {
          var field = value;
          $timeout(function() {
            if (field) {
              scope.data.where.push([field, '=', '']);
              scope.newClause = null;
            }
          });
        });
        scope.$watch('data.where', function(values) {
          // Remove empty values
          _.each(values, function(clause, index) {
            if (typeof clause !== 'undefined' && !clause[0]) {
              values.splice(index, 1);
            }
          });
        }, true);
      }
    };
  });

  angular.module('api4Explorer').directive('api4ExpValue', function($routeParams, crmApi4) {
    return {
      scope: {
        data: '=api4ExpValue'
      },
      require: 'ngModel',
      link: function (scope, element, attrs, ctrl) {
        var ts = scope.ts = CRM.ts('api4'),
          multi = _.includes(['IN', 'NOT IN'], scope.data.op),
          entity = $routeParams.api4entity,
          action = $routeParams.api4action;

        function destroyWidget() {
          var $el = $(element);
          if ($el.is('.crm-form-date-wrapper .crm-hidden-date')) {
            $el.crmDatepicker('destroy');
          }
          if ($el.is('.select2-container + input')) {
            $el.crmEntityRef('destroy');
          }
          $(element).removeData().removeAttr('type').removeAttr('placeholder').show();
        }

        function makeWidget(field, op) {
          var $el = $(element),
            inputType = field.input_type;
            dataType = field.data_type;
          if (!op) {
            op = field.serialize || dataType === 'Array' ? 'IN' : '=';
          }
          multi = _.includes(['IN', 'NOT IN'], op);
          if (op === 'IS NULL' || op === 'IS NOT NULL') {
            $el.hide();
            return;
          }
          if (inputType === 'Date') {
            if (_.includes(['=', '!=', '<>', '<', '>=', '<', '<='], op)) {
              $el.crmDatepicker({time: (field.input_attrs && field.input_attrs.time) || false});
            }
          } else if (_.includes(['=', '!=', '<>', 'IN', 'NOT IN'], op) && (field.fk_entity || field.options || dataType === 'Boolean')) {
            if (field.fk_entity) {
              $el.crmEntityRef({entity: field.fk_entity, select:{multiple: multi}});
            } else if (field.options) {
              $el.addClass('loading').attr('placeholder', ts('- select -')).crmSelect2({multiple: multi, data: [{id: '', text: ''}]});
              loadFieldOptions(field.entity || entity).then(function(data) {
                var options = [];
                _.each(_.findWhere(data, {name: field.name}).options, function(val, key) {
                  options.push({id: key, text: val});
                });
                $el.removeClass('loading').select2({data: options, multiple: multi});
              });
            } else if (dataType === 'Boolean') {
              $el.attr('placeholder', ts('- select -')).crmSelect2({allowClear: false, multiple: multi, placeholder: ts('- select -'), data: [
                {id: '1', text: ts('Yes')},
                {id: '0', text: ts('No')}
              ]});
            }
          } else if (dataType === 'Integer') {
            $el.attr('type', 'number');
          }
        }

        function loadFieldOptions(entity) {
          if (!fieldOptions[entity + action]) {
            fieldOptions[entity + action] = crmApi4(entity, 'getFields', {
              loadOptions: true,
              action: action,
              where: [["options", "!=", false]],
              select: ["name", "options"]
            });
          }
          return fieldOptions[entity + action];
        }

        // Copied from ng-list but applied conditionally if field is multi-valued
        var parseList = function(viewValue) {
          // If the viewValue is invalid (say required but empty) it will be `undefined`
          if (_.isUndefined(viewValue)) return;

          if (!multi) {
            return viewValue;
          }

          var list = [];

          if (viewValue) {
            _.each(viewValue.split(','), function(value) {
              if (value) list.push(_.trim(value));
            });
          }

          return list;
        };

        // Copied from ng-list
        ctrl.$parsers.push(parseList);
        ctrl.$formatters.push(function(value) {
          return _.isArray(value) ? value.join(', ') : value;
        });

        // Copied from ng-list
        ctrl.$isEmpty = function(value) {
          return !value || !value.length;
        };

        scope.$watchCollection('data', function(data) {
          destroyWidget();
          var field = getField(data.field, entity, action);
          if (field) {
            makeWidget(field, data.op);
          }
        });
      }
    };
  });


  angular.module('api4Explorer').directive('api4ExpChain', function(crmApi4) {
    return {
      scope: {
        chain: '=api4ExpChain',
        mainEntity: '=',
        entities: '='
      },
      templateUrl: '~/api4Explorer/Chain.html',
      link: function (scope, element, attrs) {
        var ts = scope.ts = CRM.ts('api4');

        function changeEntity(newEntity, oldEntity) {
          // When clearing entity remove this chain
          if (!newEntity) {
            scope.chain[0] = '';
            return;
          }
          // Reset action && index
          if (newEntity !== oldEntity) {
            scope.chain[1][1] = scope.chain[1][2] = '';
          }
          if (getEntity(newEntity).actions) {
            setActions();
          } else {
            crmApi4(newEntity, 'getActions', {chain: {fields: [newEntity, 'getFields', {action: '$name'}]}})
              .then(function(data) {
                getEntity(data.entity).actions = data;
                if (data.entity === scope.chain[1][0]) {
                  setActions();
                }
              });
          }
        }

        function setActions() {
          scope.actions = [''].concat(_.pluck(getEntity(scope.chain[1][0]).actions, 'name'));
        }

        // Set default params when choosing action
        function changeAction(newAction, oldAction) {
          var link;
          // Prepopulate links
          if (newAction && newAction !== oldAction) {
            // Clear index
            scope.chain[1][3] = '';
            // Look for links back to main entity
            _.each(entityFields(scope.chain[1][0]), function(field) {
              if (field.fk_entity === scope.mainEntity) {
                link = [field.name, '$id'];
              }
            });
            // Look for links from main entity
            if (!link && newAction !== 'create') {
              _.each(entityFields(scope.mainEntity), function(field) {
                if (field.fk_entity === scope.chain[1][0]) {
                  link = ['id', '$' + field.name];
                  // Since we're specifying the id, set index to getsingle
                  scope.chain[1][3] = '0';
                }
              });
            }
            if (link && _.contains(['get', 'update', 'replace', 'delete'], newAction)) {
              scope.chain[1][2] = '{where: [[' + link[0] + ', =, ' + link[1] + ']]}';
            }
            else if (link && _.contains(['create'], newAction)) {
              scope.chain[1][2] = '{values: {' + link[0] + ': ' + link[1] + '}}';
            } else {
              scope.chain[1][2] = '{}';
            }
          }
        }

        scope.$watch("chain[1][0]", changeEntity);
        scope.$watch("chain[1][1]", changeAction);
      }
    };
  });

  function getEntity(entityName) {
    return _.findWhere(schema, {name: entityName});
  }

  function entityFields(entityName, action) {
    var entity = getEntity(entityName);
    if (entity && action && entity.actions) {
      return _.findWhere(entity.actions, {name: action}).fields;
    }
    return _.result(entity, 'fields');
  }

  function getField(fieldName, entity, action) {
    var fieldNames = fieldName.split('.');
    return get(entity, fieldNames);

    function get(entity, fieldNames) {
      if (fieldNames.length === 1) {
        return _.findWhere(entityFields(entity, action), {name: fieldNames[0]});
      }
      var comboName = _.findWhere(entityFields(entity, action), {name: fieldNames[0] + '.' + fieldNames[1]});
      if (comboName) {
        return comboName;
      }
      var linkName = fieldNames.shift(),
        entityLinks = _.findWhere(links, {entity: entity}).links,
        newEntity = _.findWhere(entityLinks, {alias: linkName}).entity;
      return get(newEntity, fieldNames);
    }
  }

  // Collapsible optgroups for select2
  $(function() {
    $('body')
      .on('select2-open', function(e) {
        if ($(e.target).hasClass('collapsible-optgroups')) {
          $('#select2-drop')
            .off('.collapseOptionGroup')
            .addClass('collapsible-optgroups-enabled')
            .on('click.collapseOptionGroup', '.select2-result-with-children > .select2-result-label', function() {
              $(this).parent().toggleClass('optgroup-expanded');
            });
        }
      })
     .on('select2-close', function() {
        $('#select2-drop').off('.collapseOptionGroup').removeClass('collapsible-optgroups-enabled');
      });
  });
})(angular, CRM.$, CRM._);
