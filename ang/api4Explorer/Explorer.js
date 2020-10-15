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
  // Api params
  var params;


  angular.module('api4Explorer').config(function($routeProvider) {
    $routeProvider.when('/explorer/:api4entity?/:api4action?', {
      controller: 'Api4Explorer',
      templateUrl: '~/api4Explorer/Explorer.html',
      reloadOnSearch: false
    });
  });

  angular.module('api4Explorer').controller('Api4Explorer', function($scope, $routeParams, $location, $timeout, $http, crmUiHelp, crmApi4, dialogService) {
    var ts = $scope.ts = CRM.ts(),
      ctrl = $scope.$ctrl = this;
    $scope.entities = entities;
    $scope.actions = actions;
    $scope.fields = [];
    $scope.havingOptions = [];
    $scope.fieldsAndJoins = [];
    $scope.fieldsAndJoinsAndFunctions = [];
    $scope.fieldsAndJoinsAndFunctionsWithSuffixes = [];
    $scope.fieldsAndJoinsAndFunctionsAndWildcards = [];
    $scope.availableParams = {};
    params = $scope.params = {};
    $scope.index = '';
    $scope.selectedTab = {result: 'result', code: 'php'};
    $scope.perm = {
      accessDebugOutput: CRM.checkPerm('access debug output'),
      editGroups: CRM.checkPerm('edit groups')
    };
    marked.setOptions({highlight: prettyPrintOne});
    var getMetaParams = {},
      objectParams = {orderBy: 'ASC', values: '', defaults: '', chain: ['Entity', '', '{}']},
      docs = CRM.vars.api4.docs,
      helpTitle = '',
      helpContent = {};
    $scope.helpTitle = '';
    $scope.helpContent = {};
    $scope.entity = $routeParams.api4entity;
    $scope.result = [];
    $scope.debug = null;
    $scope.status = 'default';
    $scope.loading = false;
    $scope.controls = {};
    $scope.langs = ['php', 'js', 'ang', 'cli'];
    $scope.joinTypes = [{k: false, v: 'FALSE (LEFT JOIN)'}, {k: true, v: 'TRUE (INNER JOIN)'}];
    $scope.bridgeEntities = _.filter(schema, {type: 'BridgeEntity'});
    $scope.code = {
      php: [
        {name: 'oop', label: ts('OOP Style'), code: ''},
        {name: 'php', label: ts('Traditional'), code: ''}
      ],
      js: [
        {name: 'js', label: ts('Single Call'), code: ''},
        {name: 'js2', label: ts('Batch Calls'), code: ''}
      ],
      ang: [
        {name: 'ang', label: ts('Single Call'), code: ''},
        {name: 'ang2', label: ts('Batch Calls'), code: ''}
      ],
      cli: [
        {name: 'short', label: ts('CV (short)'), code: ''},
        {name: 'long', label: ts('CV (long)'), code: ''},
        {name: 'pipe', label: ts('CV (pipe)'), code: ''}
      ]
    };

    if (!entities.length) {
      formatForSelect2(schema, entities, 'name', ['description', 'icon']);
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
      var lastLetter = str[str.length - 1],
        lastTwo = str[str.length - 2] + lastLetter;
      if (lastLetter === 's' || lastLetter === 'x' || lastTwo === 'ch') {
        return str + 'es';
      }
      if (lastLetter === 'y' && !_.includes(['ay', 'ey', 'iy', 'oy', 'uy'], lastTwo)) {
        return str.slice(0, -1) + 'ies';
      }
      return str + 's';
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

    // Replaces contents of fieldList array with current fields formatted for select2
    function getFieldList(fieldList, action, addPseudoconstant) {
      var fieldInfo = _.cloneDeep(_.findWhere(getEntity().actions, {name: action}).fields);
      fieldList.length = 0;
      if (addPseudoconstant) {
        addPseudoconstants(fieldInfo, addPseudoconstant);
      }
      formatForSelect2(fieldInfo, fieldList, 'name', ['description', 'required', 'default_value']);
    }

    // Note: this function expects fieldList to be select2-formatted already
    function addJoins(fieldList, addWildcard, addPseudoconstant) {
      // Add entities specified by the join param
      _.each(getExplicitJoins(), function(joinEntity, joinAlias) {
        var wildCard = addWildcard ? [{id: joinAlias + '.*', text: joinAlias + '.*', 'description': 'All core ' + joinEntity + ' fields'}] : [],
          joinFields = _.cloneDeep(entityFields(joinEntity));
        if (joinFields) {
          if (addPseudoconstant) {
            addPseudoconstants(joinFields, addPseudoconstant);
          }
          fieldList.push({
            text: joinEntity + ' AS ' + joinAlias,
            description: 'Explicit join to ' + joinEntity,
            children: wildCard.concat(formatForSelect2(joinFields, [], 'name', ['description'], joinAlias + '.'))
          });
        }
      });
      // Add implicit joins based on schema links
      _.each(links[$scope.entity], function(link) {
        var linkFields = _.cloneDeep(entityFields(link.entity)),
          wildCard = addWildcard ? [{id: link.alias + '.*', text: link.alias + '.*', 'description': 'All core ' + link.entity + ' fields'}] : [];
        if (linkFields) {
          if (addPseudoconstant) {
            addPseudoconstants(linkFields, addPseudoconstant);
          }
          fieldList.push({
            text: link.alias,
            description: 'Implicit join to ' + link.entity,
            children: wildCard.concat(formatForSelect2(linkFields, [], 'name', ['description'], link.alias + '.'))
          });
        }
      });
    }

    // Note: this function transforms a raw list a-la getFields; not a select2-formatted list
    function addPseudoconstants(fieldList, toAdd) {
      var optionFields = _.filter(fieldList, 'options');
      _.each(optionFields, function(field) {
        var pos = _.findIndex(fieldList, {name: field.name}) + 1;
        _.each(toAdd, function(suffix) {
          var newField = _.cloneDeep(field);
          newField.name += ':' + suffix;
          fieldList.splice(pos, 0, newField);
        });
      });
    }

    $scope.help = function(title, content) {
      if (!content) {
        $scope.helpTitle = helpTitle;
        $scope.helpContent = helpContent;
      } else {
        $scope.helpTitle = title;
        $scope.helpContent = formatHelp(content);
      }
    };

    // Sets the static help text (which gets overridden by mousing over other elements)
    function setHelp(title, content) {
      $scope.helpTitle = helpTitle = title;
      $scope.helpContent = helpContent = formatHelp(content);
    }

    // Convert plain-text help to markdown; replace variables and format links
    function formatHelp(rawContent) {
      function formatRefs(see) {
        _.each(see, function(ref, idx) {
          var match = ref.match(/^\\Civi\\Api4\\([a-zA-Z]+)$/);
          if (match) {
            ref = '#/explorer/' + match[1];
          }
          if (ref[0] === '\\') {
            ref = 'https://github.com/civicrm/civicrm-core/blob/master' + ref.replace(/\\/i, '/') + '.php';
          }
          see[idx] = '<a target="' + (ref[0] === '#' ? '_self' : '_blank') + '" href="' + ref + '">' + see[idx] + '</a>';
        });
      }
      var formatted = _.cloneDeep(rawContent);
      if (formatted.description) {
        formatted.description = marked(formatted.description);
      }
      if (formatted.comment) {
        formatted.comment = marked(formatted.comment);
      }
      formatRefs(formatted.see);
      return formatted;
    }

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

    // Returns field list for write params (values, defaults)
    $scope.fieldList = function(param) {
      return function() {
        var fields = [];
        getFieldList(fields, $scope.action === 'getFields' ? ($scope.params.action || 'get') : $scope.action, ['name']);
        // Disable fields that are already in use
        _.each($scope.params[param] || [], function(val) {
          var usedField = val[0].replace(':name', '');
          (_.findWhere(fields, {id: usedField}) || {}).disabled = true;
          (_.findWhere(fields, {id: usedField + ':name'}) || {}).disabled = true;
        });
        return {results: fields};
      };
    };

    $scope.formatSelect2Item = function(row) {
      return _.escape(row.text) +
        (row.required ? '<span class="crm-marker"> *</span>' : '') +
        (row.description ? '<div class="crm-select2-row-description"><p>' + _.escape(row.description) + '</p></div>' : '');
    };

    $scope.clearParam = function(name, idx) {
      if (typeof idx === 'undefined') {
        $scope.params[name] = $scope.availableParams[name].default;
      } else {
        $scope.params[name].splice(idx, 1);
      }
    };

    // Gets params that should be represented as generic input fields in the explorer
    // This fn doesn't have to be particularly efficient as its output is cached in one-time bindings
    $scope.getGenericParams = function(paramType, defaultNull) {
      // Returns undefined if params are not yet set; one-time bindings will stabilize when this function returns a value
      if (_.isEmpty($scope.availableParams)) {
        return;
      }
      var specialParams = ['select', 'fields', 'action', 'where', 'values', 'defaults', 'orderBy', 'chain', 'groupBy', 'having', 'join'];
      if ($scope.availableParams.limit && $scope.availableParams.offset) {
        specialParams.push('limit', 'offset');
      }
      return _.transform($scope.availableParams, function(genericParams, param, name) {
        if (!_.contains(specialParams, name) &&
          !(typeof paramType !== 'undefined' && !_.contains(paramType, param.type[0])) &&
          !(typeof defaultNull !== 'undefined' && ((param.default === null) !== defaultNull))
        ) {
          genericParams[name] = param;
        }
      });
    };

    $scope.selectRowCount = function() {
      var index = params.select.indexOf('row_count');
      if (index < 0) {
        $scope.params.select.push('row_count');
      } else {
        $scope.params.select.splice(index, 1);
      }
    };

    $scope.isSelectRowCount = function() {
      return isSelectRowCount($scope.params);
    };

    $scope.selectLang = function(lang) {
      $scope.selectedTab.code = lang;
      writeCode();
    };

    function isSelectRowCount(params) {
      return params && params.select && params.select.indexOf('row_count') >= 0;
    }

    function getEntity(entityName) {
      return _.findWhere(schema, {name: entityName || $scope.entity});
    }

    // Get name of entity given join alias
    function entityNameFromAlias(alias) {
      var joins = getExplicitJoins(),
        entity = $scope.entity,
        path = alias.split('.');
      // First check explicit joins
      if (joins[alias]) {
        return joins[alias];
      }
      // Then lookup implicit links
      _.each(path, function(node) {
        var link = _.find(links[entity], {alias: node});
        if (!link) {
          return false;
        }
        entity = link.entity;
      });
      return entity;
    }

    // Get all params that have been set
    function getParams() {
      var params = {};
      _.each($scope.params, function(param, key) {
        if (param != $scope.availableParams[key].default && !(typeof param === 'object' && _.isEmpty(param))) {
          if (_.contains($scope.availableParams[key].type, 'array') && (typeof objectParams[key] === 'undefined')) {
            params[key] = parseYaml(JSON.parse(angular.toJson(param)));
          } else {
            params[key] = param;
          }
        }
      });
      _.each(objectParams, function(defaultVal, key) {
        if (params[key]) {
          var newParam = {};
          _.each(params[key], function(item) {
            var val = _.cloneDeep(item[1]);
            // Remove blank items from "chain" array
            if (_.isArray(val)) {
              _.eachRight(item[1], function(v, k) {
                if (v) {
                  return false;
                }
                val.length--;
              });
            }
            newParam[item[0]] = parseYaml(val);
          });
          params[key] = newParam;
        }
      });
      return params;
    }

    function parseYaml(input) {
      if (typeof input === 'undefined' || input === '') {
        return input;
      }
      // Return literal quoted string without removing quotes - for the sake of JOIN ON clauses
      if (_.isString(input) && input[0] === input[input.length - 1] && _.includes(["'", '"'], input[0])) {
        return input;
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

    this.buildFieldList = function() {
      var actionInfo = _.findWhere(actions, {id: $scope.action});
      getFieldList($scope.fields, $scope.action);
      getFieldList($scope.fieldsAndJoins, $scope.action, ['name']);
      getFieldList($scope.fieldsAndJoinsAndFunctions, $scope.action);
      getFieldList($scope.fieldsAndJoinsAndFunctionsWithSuffixes, $scope.action, ['name', 'label']);
      getFieldList($scope.fieldsAndJoinsAndFunctionsAndWildcards, $scope.action, ['name', 'label']);
      if (_.contains(['get', 'update', 'delete', 'replace'], $scope.action)) {
        addJoins($scope.fieldsAndJoins);
        // SQL functions are supported if HAVING is
        if (actionInfo.params.having) {
          var functions = {
            text: ts('FUNCTION'),
            description: ts('Calculate result of a SQL function'),
            children: _.transform(CRM.vars.api4.functions, function(result, fn) {
              result.push({
                id: fn.name + '() AS ' + fn.name.toLowerCase(),
                text: fn.name + '()',
                description: fn.name + '(' + describeSqlFn(fn.params) + ')'
              });
            })
          };
          $scope.fieldsAndJoinsAndFunctions.push(functions);
          $scope.fieldsAndJoinsAndFunctionsWithSuffixes.push(functions);
          $scope.fieldsAndJoinsAndFunctionsAndWildcards.push(functions);
        }
        addJoins($scope.fieldsAndJoinsAndFunctions, true);
        addJoins($scope.fieldsAndJoinsAndFunctionsWithSuffixes, false, ['name', 'label']);
        addJoins($scope.fieldsAndJoinsAndFunctionsAndWildcards, true, ['name', 'label']);
      }
      // Custom fields are supported if HAVING is
      if (actionInfo.params.having) {
        $scope.fieldsAndJoinsAndFunctionsAndWildcards.unshift({id: 'custom.*', text: 'custom.*', 'description': 'All custom fields'});
      }
      $scope.fieldsAndJoinsAndFunctionsAndWildcards.unshift({id: '*', text: '*', 'description': 'All core ' + $scope.entity + ' fields'});
    };

    function selectAction() {
      $scope.action = $routeParams.api4action;
      if (!actions.length) {
        formatForSelect2(getEntity().actions, actions, 'name', ['description', 'params']);
      }
      if ($scope.action) {
        var actionInfo = _.findWhere(actions, {id: $scope.action});
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
            if (name === 'limit') {
              defaultVal = 25;
            }
            if (name === 'debug') {
              defaultVal = true;
            }
            if (name === 'values') {
              defaultVal = defaultValues(defaultVal);
            }
            if (name === 'loadOptions' && $scope.action === 'getFields') {
              param.options = [
                false,
                true,
                ['id', 'name', 'label'],
                ['id', 'name', 'label', 'abbr', 'description', 'color', 'icon']
              ];
              format = 'json';
              defaultVal = false;
              param.type = ['string'];
            }
            $scope.$bindToRoute({
              expr: 'params["' + name + '"]',
              param: name,
              format: format,
              default: defaultVal,
              deep: format === 'json'
            });
          }
          if (typeof objectParams[name] !== 'undefined' && name !== 'orderBy') {
            $scope.$watch('params.' + name, function (values) {
              // Remove empty values
              _.each(values, function (clause, index) {
                if (!clause || !clause[0]) {
                  $scope.clearParam(name, index);
                }
              });
            }, true);
          }
          if (name === 'select' && actionInfo.params.having) {
            $scope.$watchCollection('params.select', function(newSelect) {
              // Ignore row_count, it can't be used in HAVING clause
              var select = _.without(newSelect, 'row_count');
              $scope.havingOptions.length = 0;
              // An empty select is an implicit *
              if (!select.length) {
                select.push('*');
              }
              _.each(select, function(item) {
                var joinEntity,
                  pieces = item.split(' AS '),
                  alias = _.trim(pieces[pieces.length - 1]).replace(':label', ':name');
                // Expand wildcards
                if (alias[alias.length - 1] === '*') {
                  if (alias.length > 1) {
                    joinEntity = entityNameFromAlias(alias.slice(0, -2));
                  }
                  var fieldList = _.filter(getEntity(joinEntity).fields, {custom_field_id: null});
                  formatForSelect2(fieldList, $scope.havingOptions, 'name', ['description', 'required', 'default_value'], alias.slice(0, -1));
                }
                else {
                  $scope.havingOptions.push({id: alias, text: alias});
                }
              });
            });
          }
          if (typeof objectParams[name] !== 'undefined' || name === 'groupBy' || name === 'select' || name === 'join') {
            $scope.$watch('controls.' + name, function(value) {
              var field = value;
              $timeout(function() {
                if (field) {
                  if (name === 'join') {
                    $scope.params[name].push([field + ' AS ' + _.snakeCase(field), false]);
                    ctrl.buildFieldList();
                  }
                  else if (typeof objectParams[name] === 'undefined') {
                    $scope.params[name].push(field);
                  } else {
                    var defaultOp = _.cloneDeep(objectParams[name]);
                    if (name === 'chain') {
                      var num = $scope.params.chain.length;
                      defaultOp[0] = field;
                      field = 'name_me_' + num;
                    }
                    $scope.params[name].push([field, defaultOp]);
                  }
                  $scope.controls[name] = null;
                }
              });
            });
          }
        });
        ctrl.buildFieldList();
        $scope.availableParams = actionInfo.params;
      }
      writeCode();
    }

    function describeSqlFn(params) {
      var desc = ' ';
      _.each(params, function(param) {
        desc += ' ';
        if (param.prefix) {
          desc += _.filter(param.prefix).join('|') + ' ';
        }
        if (param.expr === 1) {
          desc += 'expr ';
        } else if (param.expr > 1) {
          desc += 'expr, ... ';
        }
        if (param.suffix) {
          desc += ' ' + _.filter(param.suffix).join('|') + ' ';
        }
      });
      return desc.replace(/[ ]+/g, ' ');
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
      var code = {},
        entity = $scope.entity,
        action = $scope.action,
        params = getParams(),
        index = isInt($scope.index) ? +$scope.index : parseYaml($scope.index),
        result = 'result';
      if ($scope.entity && $scope.action) {
        delete params.debug;
        if (action.slice(0, 3) === 'get') {
          result = entity.substr(0, 7) === 'Custom_' ? _.camelCase(entity.substr(7)) : entity;
          result = lcfirst(action.replace(/s$/, '').slice(3) || result);
        }
        var results = lcfirst(_.isNumber(index) ? result : pluralize(result)),
          paramCount = _.size(params),
          i = 0;

        switch ($scope.selectedTab.code) {
          case 'js':
          case 'ang':
            // Write javascript
            var js = "'" + entity + "', '" + action + "', {";
            _.each(params, function(param, key) {
              js += "\n  " + key + ': ' + stringify(param) +
                (++i < paramCount ? ',' : '');
              if (key === 'checkPermissions') {
                js += ' // IGNORED: permissions are always enforced from client-side requests';
              }
            });
            js += "\n}";
            if (index || index === 0) {
              js += ', ' + JSON.stringify(index);
            }
            code.js = "CRM.api4(" + js + ").then(function(" + results + ") {\n  // do something with " + results + " array\n}, function(failure) {\n  // handle failure\n});";
            code.js2 = "CRM.api4({" + results + ': [' + js + "]}).then(function(batch) {\n  // do something with batch." + results + " array\n}, function(failure) {\n  // handle failure\n});";
            code.ang = "crmApi4(" + js + ").then(function(" + results + ") {\n  // do something with " + results + " array\n}, function(failure) {\n  // handle failure\n});";
            code.ang2 = "crmApi4({" + results + ': [' + js + "]}).then(function(batch) {\n  // do something with batch." + results + " array\n}, function(failure) {\n  // handle failure\n});";
            break;

          case 'php':
            // Write php code
            code.php = '$' + results + " = civicrm_api4('" + entity + "', '" + action + "', [";
            _.each(params, function(param, key) {
              code.php += "\n  '" + key + "' => " + phpFormat(param, 4) + ',';
            });
            code.php += "\n]";
            if (index || index === 0) {
              code.php += ', ' + phpFormat(index);
            }
            code.php += ");";

            // Write oop code
            code.oop = '$' + results + " = " + formatOOP(entity, action, params, 2) + "\n  ->execute()";
            if (_.isNumber(index)) {
              code.oop += !index ? '\n  ->first()' : (index === -1 ? '\n  ->last()' : '\n  ->itemAt(' + index + ')');
            } else if (index) {
              if (_.isString(index) || (_.isPlainObject(index) && !index[0] && !index['0'])) {
                code.oop += "\n  ->indexBy('" + (_.isPlainObject(index) ? _.keys(index)[0] : index) + "')";
              }
              if (_.isArray(index) || _.isPlainObject(index)) {
                code.oop += "\n  ->column('" + (_.isArray(index) ? index[0] : _.values(index)[0]) + "')";
              }
            }
            code.oop += ";\n";
            if (!_.isNumber(index)) {
              code.oop += "foreach ($" + results + ' as $' + ((_.isString(index) && index) ? index + ' => $' : '') + result + ') {\n  // do something\n}';
            }
            break;

          case 'cli':
            // Cli code using json input
            code.long = 'cv api4 ' + entity + '.' + action + ' ' + cliFormat(JSON.stringify(params));
            code.pipe = 'echo ' + cliFormat(JSON.stringify(params)) + ' | cv api4 ' + entity + '.' + action + ' --in=json';

            // Cli code using short syntax
            code.short = 'cv api4 ' + entity + '.' + action;
            var limitSet = false;
            _.each(params, function(param, key) {
              switch (true) {
                case (key === 'select' && !_.includes(param.join(), ' ')):
                  code.short += ' +s ' + cliFormat(param.join(','));
                  break;
                case (key === 'where' && !_.intersection(_.map(param, 0), ['AND', 'OR', 'NOT']).length):
                  _.each(param, function(clause) {
                    code.short += ' +w ' + cliFormat(clause[0] + ' ' + clause[1] + (clause.length > 2 ? (' ' + JSON.stringify(clause[2])) : ''));
                  });
                  break;
                case (key === 'orderBy'):
                  _.each(param, function(dir, field) {
                    code.short += ' +o ' + cliFormat(field + ' ' + dir);
                  });
                  break;
                case (key === 'values'):
                  _.each(param, function(val, field) {
                    code.short += ' +v ' + cliFormat(field + '=' + val);
                  });
                  break;
                case (key === 'limit' || key === 'offset'):
                  // These 2 get combined
                  if (!limitSet) {
                    limitSet = true;
                    code.short += ' +l ' + (params.limit || '0') + (params.offset ? ('@' + params.offset) : '');
                  }
                  break;
                default:
                  code.short += ' ' + key + '=' + (typeof param === 'string' ? cliFormat(param) : cliFormat(JSON.stringify(param)));
              }
            });
        }
      }
      _.each($scope.code, function(vals) {
        _.each(vals, function(style) {
          style.code = code[style.name] ? prettyPrintOne(code[style.name]) : '';
        });
      });
    }

    // Format oop params
    function formatOOP(entity, action, params, indent) {
      var code = '',
        newLine = "\n" + _.repeat(' ', indent),
        perm = params.checkPermissions === false ? 'FALSE' : '';
      if (entity.substr(0, 7) !== 'Custom_') {
        code = "\\Civi\\Api4\\" + entity + '::' + action + '(' + perm + ')';
      } else {
        code = "\\Civi\\Api4\\CustomValue::" + action + "('" + entity.substr(7) + "'" + (perm ? ', ' : '') + perm + ")";
      }
      _.each(params, function(param, key) {
        var val = '';
        if (typeof objectParams[key] !== 'undefined' && key !== 'chain') {
          _.each(param, function(item, index) {
            val = phpFormat(index) + ', ' + phpFormat(item, 2 + indent);
            code += newLine + "->add" + ucfirst(key).replace(/s$/, '') + '(' + val + ')';
          });
        } else if (key === 'where') {
          _.each(param, function (clause) {
            if (clause[0] === 'AND' || clause[0] === 'OR' || clause[0] === 'NOT') {
              code += newLine + "->addClause(" + phpFormat(clause[0]) + ", " + phpFormat(clause[1]).slice(1, -1) + ')';
            } else {
              code += newLine + "->addWhere(" + phpFormat(clause).slice(1, -1) + ")";
            }
          });
        } else if (key === 'select') {
          // selectRowCount() is a shortcut for addSelect('row_count')
          if (isSelectRowCount(params)) {
            code += newLine + '->selectRowCount()';
            param = _.without(param, 'row_count');
          }
          // addSelect() is a variadic function & can take multiple arguments
          if (param.length) {
            code += newLine + '->addSelect(' + phpFormat(param).slice(1, -1) + ')';
          }
        } else if (key === 'chain') {
          _.each(param, function(chain, name) {
            code += newLine + "->addChain('" + name + "', " + formatOOP(chain[0], chain[1], chain[2], 2 + indent);
            code += (chain.length > 3 ? ',' : '') + (!_.isEmpty(chain[2]) ? newLine : ' ') + (chain.length > 3 ? phpFormat(chain[3]) : '') + ')';
          });
        }
        else if (key !== 'checkPermissions') {
          code += newLine + "->set" + ucfirst(key) + '(' + phpFormat(param, 2 + indent) + ')';
        }
      });
      return code;
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
      return prettyPrintOne(_.escape(ret));
    }

    $scope.execute = function() {
      $scope.status = 'info';
      $scope.loading = true;
      $http.post(CRM.url('civicrm/ajax/api4/' + $scope.entity + '/' + $scope.action, {
        params: angular.toJson(getParams()),
        index: isInt($scope.index) ? +$scope.index : parseYaml($scope.index)
      }), null, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).then(function(resp) {
          $scope.loading = false;
          $scope.status = resp.data && resp.data.debug && resp.data.debug.log ? 'warning' : 'success';
          $scope.debug = debugFormat(resp.data);
          $scope.result = [
            formatMeta(resp.data),
            prettyPrintOne((_.isArray(resp.data.values) ? '(' + resp.data.values.length + ') ' : '') + _.escape(JSON.stringify(resp.data.values, null, 2)), 'js', 1)
          ];
        }, function(resp) {
          $scope.loading = false;
          $scope.status = 'danger';
          $scope.debug = debugFormat(resp.data);
          $scope.result = [
            formatMeta(resp),
            prettyPrintOne(_.escape(JSON.stringify(resp.data, null, 2)))
          ];
        });
    };

    function debugFormat(data) {
      var debug = data.debug ? prettyPrintOne(_.escape(JSON.stringify(data.debug, null, 2)).replace(/\\n/g, "\n")) : null;
      delete data.debug;
      return debug;
    }

    /**
     * Format value to look like php code
     */
    function phpFormat(val, indent) {
      if (typeof val === 'undefined') {
        return '';
      }
      if (val === null || val === true || val === false) {
        return JSON.stringify(val).toUpperCase();
      }
      indent = (typeof indent === 'number') ? _.repeat(' ', indent) : (indent || '');
      var ret = '',
        baseLine = indent ? indent.slice(0, -2) : '',
        newLine = indent ? '\n' : '',
        trailingComma = indent ? ',' : '';
      if ($.isPlainObject(val)) {
        $.each(val, function(k, v) {
          ret += (ret ? ', ' : '') + newLine + indent + "'" + k + "' => " + phpFormat(v);
        });
        return '[' + ret + trailingComma + newLine + baseLine + ']';
      }
      if ($.isArray(val)) {
        $.each(val, function(k, v) {
          ret += (ret ? ', ' : '') + newLine + indent + phpFormat(v);
        });
        return '[' + ret + trailingComma + newLine + baseLine + ']';
      }
      if (_.isString(val) && !_.contains(val, "'")) {
        return "'" + val + "'";
      }
      return JSON.stringify(val).replace(/\$/g, '\\$');
    }

    // Format string to be cli-input-safe
    function cliFormat(str) {
      if (!_.includes(str, ' ') && !_.includes(str, '"') && !_.includes(str, "'")) {
        return str;
      }
      if (!_.includes(str, "'")) {
        return "'" + str + "'";
      }
      if (!_.includes(str, '"')) {
        return '"' + str + '"';
      }
      return "'" + str.replace(/'/g, "\\'") + "'";
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
      setHelp($scope.entity, {
        description: entityInfo.description,
        comment: entityInfo.comment,
        see: entityInfo.see
      });
    }

    if (!$scope.entity) {
      setHelp(ts('APIv4 Explorer'), {description: docs.description, comment: docs.comment, see: docs.see});
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
        setHelp($scope.entity + '::' + newVal, _.pick(_.findWhere(getEntity().actions, {name: newVal}), ['description', 'comment', 'see']));
      }
    });

    $scope.paramDoc = function(name) {
      return docs.params[name];
    };

    $scope.executeDoc = function() {
      var doc = {
        description: ts('Runs API call on the CiviCRM database.'),
        comment: ts('Results and debugging info will be displayed below.')
      };
      if ($scope.action === 'delete') {
        doc.WARNING = ts('This API call will be executed on the real database. Deleting data cannot be undone.');
      }
      else if ($scope.action && $scope.action.slice(0, 3) !== 'get') {
        doc.WARNING = ts('This API call will be executed on the real database. It cannot be undone.');
      }
      return doc;
    };

    $scope.saveDoc = function() {
      return {
        description: ts('Save API call as a smart group.'),
        comment: ts('Create a SavedSearch using these API params to populate a smart group.') +
          '\n\n' + ts('NOTE: you must select contact id as the only field.')
      };
    };

    $scope.$watch('params', writeCode, true);
    $scope.$watch('index', writeCode);
    writeCode();

    $scope.save = function() {
      $scope.params.limit = $scope.params.offset = 0;
      if ($scope.params.chain.length) {
        CRM.alert(ts('Smart groups are not compatible with API chaining.'), ts('Error'), 'error', {expires: 5000});
        return;
      }
      if ($scope.params.select.length !== 1 || !_.includes($scope.params.select[0], 'id')) {
        CRM.alert(ts('To create a smart group, the API must select contact id and no other fields.'), ts('Error'), 'error', {expires: 5000});
        return;
      }
      var model = {
        title: '',
        description: '',
        visibility: 'User and User Admin Only',
        group_type: [],
        id: null,
        entity: $scope.entity,
        params: JSON.parse(angular.toJson($scope.params))
      };
      model.params.version = 4;
      delete model.params.chain;
      delete model.params.debug;
      delete model.params.limit;
      delete model.params.offset;
      delete model.params.orderBy;
      delete model.params.checkPermissions;
      var options = CRM.utils.adjustDialogDefaults({
        width: '500px',
        autoOpen: false,
        title: ts('Save smart group')
      });
      dialogService.open('saveSearchDialog', '~/api4Explorer/SaveSearch.html', model, options);
    };
  });

  angular.module('api4Explorer').controller('SaveSearchCtrl', function($scope, crmApi4, dialogService) {
    var ts = $scope.ts = CRM.ts(),
      model = $scope.model;
    $scope.groupEntityRefParams = {
      entity: 'Group',
      api: {
        params: {is_hidden: 0, is_active: 1, 'saved_search_id.api_entity': model.entity},
        extra: ['saved_search_id', 'description', 'visibility', 'group_type']
      },
      select: {
        allowClear: true,
        minimumInputLength: 0,
        placeholder: ts('Select existing group')
      }
    };
    if (!CRM.checkPerm('administer reserved groups')) {
      $scope.groupEntityRefParams.api.params.is_reserved = 0;
    }
    $scope.perm = {
      administerReservedGroups: CRM.checkPerm('administer reserved groups')
    };
    $scope.options = CRM.vars.api4.groupOptions;
    $scope.$watch('model.id', function(id) {
      if (id) {
        _.assign(model, $('#api-save-search-select-group').select2('data').extra);
      }
    });
    $scope.cancel = function() {
      dialogService.cancel('saveSearchDialog');
    };
    $scope.save = function() {
      $('.ui-dialog:visible').block();
      var group = model.id ? {id: model.id} : {title: model.title};
      group.description = model.description;
      group.visibility = model.visibility;
      group.group_type = model.group_type;
      group.saved_search_id = '$id';
      var savedSearch = {
        api_entity: model.entity,
        api_params: model.params
      };
      if (group.id) {
        savedSearch.id = model.saved_search_id;
      }
      crmApi4('SavedSearch', 'save', {records: [savedSearch], chain: {group: ['Group', 'save', {'records': [group]}]}})
        .then(function(result) {
          dialogService.close('saveSearchDialog', result[0]);
        });
    };
  });

  angular.module('api4Explorer').component('crmApi4Clause', {
    bindings: {
      fields: '<',
      clauses: '<',
      format: '@',
      op: '@',
      skip: '<',
      isRequired: '<',
      label: '@',
      deleteGroup: '&'
    },
    templateUrl: '~/api4Explorer/Clause.html',
    controller: function ($scope, $element, $timeout) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;
      this.conjunctions = {AND: ts('And'), OR: ts('Or'), NOT: ts('Not')};
      this.operators = CRM.vars.api4.operators;
      this.sortOptions = {
        axis: 'y',
        connectWith: '.api4-clause-group-sortable',
        containment: $element.closest('.api4-clause-fieldset'),
        over: onSortOver,
        start: onSort,
        stop: onSort
      };

      this.$onInit = function() {
        ctrl.hasParent = !!$element.attr('delete-group');
      };

      this.addGroup = function(op) {
        ctrl.clauses.push([op, []]);
      };

      function onSort(event, ui) {
        $($element).closest('.api4-clause-fieldset').toggleClass('api4-sorting', event.type === 'sortstart');
        $('.api4-input.form-inline').css('margin-left', '');
      }

      // Indent clause while dragging between nested groups
      function onSortOver(event, ui) {
        var offset = 0;
        if (ui.sender) {
          offset = $(ui.placeholder).offset().left - $(ui.sender).offset().left;
        }
        $('.api4-input.form-inline.ui-sortable-helper').css('margin-left', '' + offset + 'px');
      }

      this.addClause = function() {
        $timeout(function() {
          if (ctrl.newClause) {
            if (ctrl.skip && ctrl.clauses.length < ctrl.skip) {
              ctrl.clauses.push(null);
            }
            ctrl.clauses.push([ctrl.newClause, '=', '']);
            ctrl.newClause = null;
          }
        });
      };

      this.deleteRow = function(index) {
        ctrl.clauses.splice(index, 1);
      };

      // Remove empty values
      this.changeClauseField = function(clause, index) {
        if (clause[0] === '') {
          ctrl.deleteRow(index);
        }
      };

      // Add/remove value if operator allows for one
      this.changeClauseOperator = function(clause) {
        if (_.contains(clause[1], 'NULL')) {
          clause.length = 2;
        } else if (clause.length === 2) {
          clause.push('');
        }
      };
    }
  });

  angular.module('api4Explorer').directive('api4ExpValue', function($routeParams, crmApi4) {
    return {
      scope: {
        data: '=api4ExpValue'
      },
      require: 'ngModel',
      link: function (scope, element, attrs, ctrl) {
        var ts = scope.ts = CRM.ts(),
          multi = _.includes(['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], scope.data.op),
          entity = $routeParams.api4entity,
          action = scope.data.action || $routeParams.api4action;

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
            inputType = field.input_type,
            dataType = field.data_type;
          if (!op) {
            op = field.serialize || dataType === 'Array' ? 'IN' : '=';
          }
          multi = _.includes(['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], op);
          if (op === 'IS NULL' || op === 'IS NOT NULL') {
            $el.hide();
            return;
          }
          if (inputType === 'Date') {
            if (_.includes(['=', '!=', '<>', '<', '>=', '<', '<='], op)) {
              $el.crmDatepicker({time: (field.input_attrs && field.input_attrs.time) || false});
            }
          } else if (_.includes(['=', '!=', '<>', 'IN', 'NOT IN'], op) && (field.fk_entity || field.options || dataType === 'Boolean')) {
           if (field.options) {
              var id = field.pseudoconstant || 'id';
              $el.addClass('loading').attr('placeholder', ts('- select -')).crmSelect2({multiple: multi, data: [{id: '', text: ''}]});
              loadFieldOptions(field.entity || entity).then(function(data) {
                var options = _.transform(data[field.name].options, function(options, opt) {
                  options.push({id: opt[id], text: opt.label, description: opt.description, color: opt.color, icon: opt.icon});
                }, []);
                $el.removeClass('loading').crmSelect2({data: options, multiple: multi});
              });
            } else if (field.fk_entity) {
              $el.crmEntityRef({entity: field.fk_entity, select:{multiple: multi}});
            } else if (dataType === 'Boolean') {
              $el.attr('placeholder', ts('- select -')).crmSelect2({allowClear: false, multiple: multi, placeholder: ts('- select -'), data: [
                {id: 'true', text: ts('Yes')},
                {id: 'false', text: ts('No')}
              ]});
            }
          } else if (dataType === 'Integer' && !multi) {
            $el.attr('type', 'number');
          }
        }

        function loadFieldOptions(entity) {
          if (!fieldOptions[entity + action]) {
            fieldOptions[entity + action] = crmApi4(entity, 'getFields', {
              loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
              action: action,
              where: [['options', '!=', false]],
              select: ['options']
            }, 'name');
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
          if (field && data.format !== 'plain') {
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
        var ts = scope.ts = CRM.ts();

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
            }
            else if (link && _.contains(['save'], newAction)) {
              scope.chain[1][2] = '{records: [{' + link[0] + ': ' + link[1] + '}]}';
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

  function getExplicitJoins() {
    return _.transform(params.join, function(joins, join) {
      var j = join[0].split(' AS '),
        joinEntity = _.trim(j[0]),
        joinAlias = _.trim(j[1]) || joinEntity.toLowerCase();
      joins[joinAlias] = joinEntity;
    }, {});
  }

  function getField(fieldName, entity, action) {
    var suffix = fieldName.split(':')[1];
    fieldName = fieldName.split(':')[0];
    var fieldNames = fieldName.split('.');
    var field = get(entity, fieldNames);
    if (field && suffix) {
      field.pseudoconstant = suffix;
    }
    return field;

    function get(entity, fieldNames) {
      if (fieldNames.length === 1) {
        return _.findWhere(entityFields(entity, action), {name: fieldNames[0]});
      }
      var comboName = _.findWhere(entityFields(entity, action), {name: fieldNames[0] + '.' + fieldNames[1]});
      if (comboName) {
        return comboName;
      }
      var linkName = fieldNames.shift(),
        newEntity = getExplicitJoins()[linkName] || _.findWhere(links[entity], {alias: linkName}).entity;
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
