(function(angular, $, _) {
  "use strict";

  // Schema metadata
  const schema = CRM.vars.api4.schema;
  // Cache list of entities
  const entities = [];
  // Field options
  const fieldOptions = {};
  // Api params
  let params;

  angular.module('api4Explorer').config(function($routeProvider) {
    $routeProvider.when('/explorer/:api4entity?/:api4action?', {
      controller: 'Api4Explorer',
      templateUrl: '~/api4Explorer/Explorer.html',
      reloadOnSearch: false
    });
  });

  angular.module('api4Explorer').controller('Api4Explorer', function($scope, $routeParams, $location, $timeout, $http, crmUiHelp, crmApi4) {
    const ts = $scope.ts = CRM.ts();
    const ctrl = $scope.$ctrl = this;
    $scope.entities = entities;
    $scope.actions = [];
    $scope.fields = [];
    $scope.havingOptions = [];
    $scope.fieldsAndJoins = [];
    $scope.fieldsAndJoinsAndFunctions = [];
    $scope.fieldsAndJoinsAndFunctionsWithSuffixes = [];
    $scope.fieldsAndJoinsAndFunctionsAndWildcards = [];
    $scope.availableParams = {};
    params = $scope.params = {};
    $scope.index = '';
    $scope.selectedTab = {result: 'result'};
    $scope.crmUrl = CRM.url;
    $scope.perm = {
      viewDebugOutput: CRM.checkPerm('view debug output'),
    };
    marked.setOptions({highlight: prettyPrintOne});
    const objectParams = {orderBy: 'ASC', values: '', defaults: '', chain: ['Entity', '', '{}']};
    const docs = CRM.vars.api4.docs;
    let response,
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
    $scope.langs = ['php', 'js', 'ang', 'cli', 'rest'];
    $scope.joinTypes = [{k: 'LEFT', v: 'LEFT JOIN'}, {k: 'INNER', v: 'INNER JOIN'}, {k: 'EXCLUDE', v: 'EXCLUDE'}];
    $scope.bridgeEntities = _.filter(schema, function(entity) {return _.includes(entity.type, 'EntityBridge');});
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
      ],
      rest: [
        {name: 'curl', label: ts('Curl'), code: ''},
        {name: 'restphp', label: ts('PHP (std)'), code: ''},
        {name: 'guzzle', label: ts('PHP + Guzzle'), code: ''}
      ]
    };
    this.resultFormats = [
      {
        name: 'json',
        label: ts('View as JSON')
      },
      {
        name: 'php',
        label: ts('View as PHP')
      },
    ];
    this.authxEnabled = CRM.vars.api4.authxEnabled;

    if (!entities.length) {
      formatForSelect2(schema, entities, 'name', ['description', 'icon']);
    }

    // Prefix other url args with an underscore to avoid conflicts with param names
    $scope.$bindToRoute({
      expr: 'index',
      param: '_index',
      default: ''
    });
    $scope.$bindToRoute({
      expr: 'selectedTab.code',
      param: '_lang',
      format: 'raw',
      default: 'php'
    });
    $scope.$bindToRoute({
      expr: '$ctrl.resultFormat',
      param: '_format',
      format: 'raw',
      default: 'json'
    });

    // Copy text to the clipboard
    this.copyCode = function(domId) {
      const node = document.getElementById(domId);
      const range = document.createRange();
      range.selectNode(node);
      window.getSelection().removeAllRanges();
      window.getSelection().addRange(range);
      document.execCommand('copy');
    };

    function ucfirst(str) {
      return str[0].toUpperCase() + str.slice(1);
    }

    function lcfirst(str) {
      return str[0].toLowerCase() + str.slice(1);
    }

    function pluralize(str) {
      const lastLetter = str[str.length - 1],
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
        const id = (prefix || '') + item[key];
        let formatted = {id: id, text: id};
        if (extra) {
          _.merge(formatted, _.pick(item, extra));
        }
        container.push(formatted);
      });
      return container;
    }

    // Replaces contents of fieldList array with current fields formatted for select2
    function getFieldList(fieldList, action, addPseudoconstant, addWriteJoins) {
      fieldList.length = 0;
      const entityInfo = getEntity();
      const actionInfo = _.findWhere(entityInfo.actions, {name: action});
      // Avoid crash before metadata has been fetched
      if (actionInfo) {
        const fieldInfo = _.cloneDeep(actionInfo.fields);
        if (addPseudoconstant) {
          addPseudoconstants(fieldInfo);
        }
        if (addWriteJoins) {
          addWriteJoinFields(fieldInfo);
        }
        formatForSelect2(fieldInfo, fieldList, 'name', ['description', 'required', 'default_value']);
      }
    }

    // Note: this function expects fieldList to be select2-formatted already
    function addJoins(fieldList, addWildcard, addPseudoconstant) {
      // Add entities specified by the join param
      _.each(getExplicitJoins(), function(join) {
        let wildCard = addWildcard ? [{id: join.alias + '.*', text: join.alias + '.*', 'description': 'All core ' + join.entity + ' fields'}] : [],
          joinFields = _.cloneDeep(entityFields(join.entity));
        if (joinFields) {
          // Add fields from bridge entity
          if (join.bridge) {
            let bridgeFields = _.cloneDeep(entityFields(join.bridge)),
              bridgeEntity = getEntity(join.bridge),
              joinFieldNames = _.pluck(joinFields, 'name'),
              // Check if this is a symmetric bridge e.g. RelationshipCache joins Contact to Contact
              bridgePair = _.keys(bridgeEntity.bridge),
              symmetric = getField(bridgePair[0], join.bridge).entity === getField(bridgePair[1], join.bridge).entity;
            _.each(bridgeFields, function(field) {
              if (
                // Only include bridge fields that link back to the original entity
                (!bridgeEntity.bridge[field.name] || field.fk_entity !== join.entity || symmetric) &&
                // Exclude fields with the same name as those in the original entity
                !_.includes(joinFieldNames, field.name)
              ) {
                joinFields.push(field);
              }
            });
          }
          if (addPseudoconstant) {
            addPseudoconstants(joinFields);
          }
          fieldList.push({
            text: join.entity + ' AS ' + join.alias,
            description: 'Explicit join to ' + join.entity,
            children: wildCard.concat(formatForSelect2(joinFields, [], 'name', ['description'], join.alias + '.'))
          });
        }
      });
      // Add implicit joins based on schema links
      _.each(entityFields($scope.entity, $scope.action), function(field) {
        if (field.fk_entity) {
          let linkFields = _.cloneDeep(entityFields(field.fk_entity)),
            wildCard = addWildcard ? [{id: field.name + '.*', text: field.name + '.*', 'description': 'All core ' + field.fk_entity + ' fields'}] : [];
          if (addPseudoconstant) {
            addPseudoconstants(linkFields);
          }
          fieldList.push({
            text: field.name,
            description: 'Implicit join to ' + field.fk_entity,
            children: wildCard.concat(formatForSelect2(linkFields, [], 'name', ['description'], field.name + '.'))
          });
        }
      });
    }

    // Note: this function transforms a raw list a-la getFields; not a select2-formatted list
    function addPseudoconstants(fieldList) {
      let optionFields = _.filter(fieldList, 'options');
      _.each(optionFields, function(field) {
        let pos = _.findIndex(fieldList, {name: field.name}) + 1;
        _.each(field.suffixes, function(suffix) {
          let newField = _.cloneDeep(field);
          newField.name += ':' + suffix;
          fieldList.splice(pos, 0, newField);
        });
      });
    }

    // Adds join fields for create actions
    // Note: this function transforms a raw list a-la getFields; not a select2-formatted list
    function addWriteJoinFields(fieldList) {
      _.eachRight(fieldList, function(field, pos) {
        const fkNameField = field.fk_entity && getField('name', field.fk_entity, $scope.action);
        if (fkNameField) {
          const newField = _.cloneDeep(fkNameField);
          newField.name = field.name + '.' + newField.name;
          fieldList.splice(pos, 0, newField);
        }
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

    // Format help text with markdown; replace variables and format links
    function formatHelp(rawContent) {
      function formatRefs(see) {
        _.each(see, function(ref, idx) {
          const match = ref.match(/^(\\Civi\\Api4\\)?([a-zA-Z]+)$/);
          if (match) {
            ref = '#/explorer/' + match[2];
          }
          // Link to php classes on GitHub.
          // Fixme: Only works for files in the core repo
          if (ref[0] === '\\' || ref.indexOf('Civi\\') === 0 || ref.indexOf('CRM_') === 0) {
            let classFunction = _.trim(ref, '\\').split('::'),
              replacement = new RegExp(classFunction[0].indexOf('CRM_') === 0 ? '_' : '\\\\', 'g');
            ref = 'https://github.com/civicrm/civicrm-core/blob/master/' + classFunction[0].replace(replacement, '/') + '.php';
          }
          see[idx] = '<a target="' + (ref[0] === '#' ? '_self' : '_blank') + '" href="' + ref + '">' + see[idx] + '</a>';
        });
      }
      const formatted = _.cloneDeep(rawContent);
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
      const field = getField(fieldName, $scope.entity, $scope.action);
      if (!field) {
        return;
      }
      const info = {
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
        const fields = [];
        getFieldList(fields, $scope.action === 'getFields' ? ($scope.params.action || 'get') : $scope.action, true, true);
        // Disable fields that are already in use
        _.each($scope.params[param] || [], function(val) {
          const usedField = val[0].replace(/[:.]name/, '');
          (_.findWhere(fields, {id: usedField}) || {}).disabled = true;
          (_.findWhere(fields, {id: usedField + ':name'}) || {}).disabled = true;
          (_.findWhere(fields, {id: usedField + '.name'}) || {}).disabled = true;
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
      const specialParams = ['select', 'fields', 'action', 'where', 'values', 'defaults', 'orderBy', 'chain', 'groupBy', 'having', 'join', 'sets'];
      if ($scope.availableParams.limit && $scope.availableParams.offset) {
        specialParams.push('limit', 'offset');
      }
      return _.transform($scope.availableParams, function(genericParams, param, name) {
        if (!_.contains(specialParams, name) && !param.deprecated &&
          !(typeof paramType !== 'undefined' && !_.contains(paramType, param.type[0])) &&
          !(typeof defaultNull !== 'undefined' && ((param.default === null) !== defaultNull))
        ) {
          genericParams[name] = param;
        }
      });
    };

    $scope.selectRowCount = function() {
      const index = params.select.indexOf('row_count');
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
      const joins = getExplicitJoins(),
        path = alias.split('.');
      let entity = $scope.entity;
      // First check explicit joins
      if (joins[alias]) {
        return joins[alias].entity;
      }
      // Then lookup implicit joins
      path.forEach(function(node) {
        const field = getField(node, entity, $scope.action);
        if (!field || !field.fk_entity) {
          return false;
        }
        entity = field.fk_entity;
      });
      return entity;
    }

    // Get all params that have been set
    function getParams() {
      const params = {};
      _.each($scope.params, function(param, key) {
        if (param != $scope.availableParams[key].default && !(typeof param === 'object' && _.isEmpty(param))) {
          if (_.contains($scope.availableParams[key].type, 'array') && (typeof objectParams[key] === 'undefined')) {
            params[key] = parseYaml(JSON.parse(angular.toJson(param)));
          } else {
            params[key] = param;
          }
        }
      });
      _.each(params.join, function(join) {
        // Add alias if not specified
        if (!_.contains(join[0], 'AS')) {
          join[0] += ' AS ' + join[0].toLowerCase();
        }
        // Remove EntityBridge from join if empty
        if (!join[2]) {
          join.splice(2, 1);
        }
      });
      _.each(objectParams, function(defaultVal, key) {
        if (params[key]) {
          const newParam = {};
          _.each(params[key], function(item) {
            let val = _.cloneDeep(item[1]);
            // Remove blank items from "chain" array
            if (Array.isArray(val)) {
              _.eachRight(item[1], function(v) {
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
      if ((typeof input === 'string') && input[0] === input[input.length - 1] && ["'", '"'].includes(input[0])) {
        return input;
      }
      if (typeof input === 'object') {
        // Could be an object or an array
        _.each(input, function(item, index) {
          input[index] = parseYaml(item);
        });
        return input;
      }
      try {
        let output = (input === '>') ? '>' : jsyaml.safeLoad(input);
        // We don't want dates parsed to js objects
        return _.isDate(output) ? input : output;
      } catch (e) {
        return input;
      }
    }

    this.buildFieldList = function() {
      const actionInfo = _.findWhere($scope.actions, {id: $scope.action});
      getFieldList($scope.fields, $scope.action);
      getFieldList($scope.fieldsAndJoins, $scope.action, true);
      getFieldList($scope.fieldsAndJoinsAndFunctions, $scope.action);
      getFieldList($scope.fieldsAndJoinsAndFunctionsWithSuffixes, $scope.action, true);
      getFieldList($scope.fieldsAndJoinsAndFunctionsAndWildcards, $scope.action, true);
      if (_.contains(['get', 'update', 'delete', 'replace'], $scope.action)) {
        addJoins($scope.fieldsAndJoins);
        // SQL functions are supported if HAVING is
        if (actionInfo.params.having) {
          const functions = {
            text: ts('FUNCTION'),
            description: ts('Calculate result of a SQL function'),
            children: _.transform(CRM.vars.api4.functions, function(result, fn) {
              result.push({
                id: fn.name + '() AS ' + fn.name.toLowerCase(),
                description: fn.description,
                text: fn.name + '(' + describeSqlFn(fn.params) + ')'
              });
            })
          };
          $scope.fieldsAndJoinsAndFunctions.push(functions);
          $scope.fieldsAndJoinsAndFunctionsWithSuffixes.push(functions);
          $scope.fieldsAndJoinsAndFunctionsAndWildcards.push(functions);
        }
        addJoins($scope.fieldsAndJoinsAndFunctions, true);
        addJoins($scope.fieldsAndJoinsAndFunctionsWithSuffixes, false, true);
        addJoins($scope.fieldsAndJoinsAndFunctionsAndWildcards, true, true);
      }
      // Custom fields are supported if HAVING is
      if (actionInfo.params.having) {
        $scope.fieldsAndJoinsAndFunctionsAndWildcards.unshift({id: 'custom.*', text: 'custom.*', 'description': 'All custom fields'});
      }
      $scope.fieldsAndJoinsAndFunctionsAndWildcards.unshift({id: '*', text: '*', 'description': 'All core ' + $scope.entity + ' fields'});
    };

    // Select2 formatter: Add 'strikethrough' class to deprecated items
    $scope.formatResultCssClass = function(result) {
      return result.deprecated ? 'strikethrough' : '';
    };

    function selectAction() {
      $scope.action = $routeParams.api4action;
      $scope.actions.length = 0;
      formatForSelect2(getEntity().actions, $scope.actions, 'name', ['description', 'params', 'deprecated']);
      if ($scope.action) {
        const actionInfo = _.findWhere($scope.actions, {id: $scope.action});
        _.each(actionInfo.params, function (param, name) {
          let format,
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
            if (name === 'limit' && $scope.action === 'get') {
              defaultVal = 25;
            }
            if (name === 'values') {
              defaultVal = defaultValues(defaultVal);
            }
            if (name === 'loadOptions' && $scope.action === 'getFields') {
              param.options = [
                false,
                true,
                ['id', 'name', 'label'],
                CRM.vars.api4.suffixes
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
              let select = _.without(newSelect, 'row_count');
              $scope.havingOptions.length = 0;
              // An empty select is an implicit *
              if (!select.length) {
                select.push('*');
              }
              _.each(select, function(item) {
                let joinEntity,
                  pieces = item.split(' AS '),
                  alias = _.trim(pieces[pieces.length - 1]).replace(':label', ':name');
                // Expand wildcards
                if (alias[alias.length - 1] === '*') {
                  if (alias.length > 1) {
                    joinEntity = entityNameFromAlias(alias.slice(0, -2));
                  }
                  let fieldList = _.filter(getEntity(joinEntity).fields, {custom_field_id: null});
                  formatForSelect2(fieldList, $scope.havingOptions, 'name', ['description', 'required', 'default_value'], alias.slice(0, -1));
                }
                else {
                  $scope.havingOptions.push({id: alias, text: alias});
                }
              });
            });
          }
          if (typeof objectParams[name] !== 'undefined' || name === 'groupBy' || name === 'select' || name === 'join' || name === 'sets') {
            $scope.$watch('controls.' + name, function(value) {
              let field = value;
              $timeout(function() {
                if (field) {
                  if (name === 'join') {
                    $scope.params[name].push([field + ' AS ' + _.snakeCase(field), 'LEFT']);
                    ctrl.buildFieldList();
                  }
                  else if (name === 'sets') {
                    let select = $scope.params.select && $scope.params.select.length ? $scope.params.select : ['id'];
                    $scope.params[name].push(['UNION ALL', field, 'get', '{select: [' + select.join(', ') + '], where: []}']);
                  }
                  else if (typeof objectParams[name] === 'undefined') {
                    $scope.params[name].push(field);
                  } else {
                    let defaultOp = _.cloneDeep(objectParams[name]);
                    if (name === 'chain') {
                      const num = $scope.params.chain.length;
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
      let desc = ' ';
      _.each(params, function(param) {
        desc += ' ';
        if (param.name) {
          desc += param.name + ' ';
        }
        if (!_.isEmpty(param.flag_before)) {
          desc += '[' + _.filter(param.name ? [param.name] : _.keys(param.flag_before)).join('|') + '] ';
        }
        if (param.max_expr === 1) {
          desc += 'expr ';
        } else if (param.max_expr > 1) {
          desc += 'expr, ... ';
        }
        if (!_.isEmpty(param.flag_after)) {
          desc += ' [' + _.filter(param.flag_after).join('|') + '] ';
        }
      });
      return desc.replace(/[ ]+/g, ' ');
    }

    function defaultValues(defaultVal) {
      $scope.fields.forEach((field) => {
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
      let str = JSON.stringify(value).replace(/,/g, ', ');
      if (trim) {
        str = str.slice(1, -1);
      }
      return str.trim();
    }

    // Url-encode suitable for use in a bash script
    function curlEscape(str) {
      return encodeURIComponent(str).
        replace(/['()*]/g, function(c) {
          return "%" + c.charCodeAt(0).toString(16);
        });
    }

    function writeCode() {
      let code = {},
        entity = $scope.entity,
        action = $scope.action,
        params = getParams(),
        index = isInt($scope.index) ? +$scope.index : parseYaml($scope.index),
        result = 'result';
      if ($scope.entity && $scope.action) {
        if (action.slice(0, 3) === 'get') {
          let args = getEntity(entity).class_args || [];
          result = args[0] ? _.camelCase(args[0]) : entity;
          result = lcfirst(action.replace(/s$/, '').slice(3) || result);
        }
        let results = lcfirst((typeof index === 'number') ? result : pluralize(result)),
          paramCount = _.size(params),
          i = 0;

        switch ($scope.selectedTab.code) {
          case 'js':
          case 'ang':
            // Write javascript
            let js = "'" + entity + "', '" + action + "', {";
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
            // Always shows implicit true permissions check for PHP
            params.checkPermissions = (params.checkPermissions !== false);
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
            // Index: numeric input = itemAt()
            if (typeof index === 'number') {
              code.oop += !index ? '\n  ->first()' : (index === -1 ? '\n  ->last()' : '\n  ->itemAt(' + index + ')');
            }
            // Index: string input = indexBy()
            else if (typeof index === 'string' && index.length) {
              code.oop += "\n  ->indexBy(" + phpFormat(index) + ")";
            }
            // Index: array input = column() with 1 arg
            else if (Array.isArray(index)) {
              code.oop += "\n  ->column(" + phpFormat(index[0]) + ")";
            }
            // Index: object input = column() with 2 args
            else if (typeof index === 'object') {
              let indexKey = Object.keys(index)[0];
              code.oop += "\n  ->column(" + phpFormat(index[indexKey]) + ", " + phpFormat(indexKey) + ")";
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
            let limitSet = false;
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
                case (typeof param === 'boolean'):
                  code.short += ' ' + key + '=' + (param ? 1 : 0);
                  break;
                default:
                  code.short += ' ' + key + '=' + (typeof param === 'string' ? cliFormat(param) : cliFormat(JSON.stringify(param)));
              }
            });
            break;

          case 'rest':
            let restUrl = CRM.vars.api4.restUrl
              .replace('CRMAPI4ENTITY', entity)
              .replace('CRMAPI4ACTION', action);
            let cleanUrl;
            if (CRM.vars.api4.restUrl.endsWith('/CRMAPI4ENTITY/CRMAPI4ACTION')) {
              cleanUrl = CRM.vars.api4.restUrl.replace('/CRMAPI4ENTITY/CRMAPI4ACTION', '/');
            }
            let restCred = 'Bearer MY_API_KEY';

            // CURL
            code.curl =
              "CRM_URL='" + restUrl + "'\n" +
              "CRM_AUTH='X-Civi-Auth: " + restCred + "'\n\n" +
              'curl -X POST -H "$CRM_AUTH" "$CRM_URL" \\' + "\n" +
              "-d 'params=" + curlEscape(JSON.stringify(params));
            if (index || index === 0) {
              code.curl += '&index=' + curlEscape(JSON.stringify(index));
            }
            code.curl += "'";

            let queryParams = "['params' => json_encode($params)" +
              ((typeof index === 'number') ? ", 'index' => " + JSON.stringify(index) : '') +
              ((index && typeof index !== 'number') ? ", 'index' => json_encode(" + phpFormat(index) + ')' : '') +
              "]";

            // Guzzle
            code.guzzle =
              "$params = " + phpFormat(params, 2) + ";\n" +
              "$client = new \\GuzzleHttp\\Client([\n" +
              (cleanUrl ? "  'base_uri' => '" + cleanUrl + "',\n" : '') +
              "  'headers' => ['X-Civi-Auth' => " + phpFormat(restCred) + "],\n" +
              "]);\n" +
              "$response = $client->get('" + (cleanUrl ? entity + '/' + action : restUrl) + "', [\n" +
              "  'form_params' => " + queryParams + ",\n" +
              "]);\n" +
              '$' + results + " = json_decode((string) $response->getBody(), TRUE);";

            // PHP StdLib
            code.restphp =
              "$url = '" + restUrl + "';\n" +
              "$params = " + phpFormat(params, 2) + ";\n" +
              "$request = stream_context_create([\n" +
              "  'http' => [\n" +
              "    'method' => 'POST',\n" +
              "    'header' => [\n" +
              "      'Content-Type: application/x-www-form-urlencoded',\n" +
              "      " + phpFormat('X-Civi-Auth: ' + restCred) + ",\n" +
              "    ],\n" +
              "    'content' => http_build_query(" + queryParams + "),\n" +
              "  ]\n" +
              "]);\n" +
              '$' + results + " = json_decode(file_get_contents($url, FALSE, $request), TRUE);\n";
        }
      }
      _.each($scope.code, function(vals) {
        _.each(vals, function(style) {
          style.code = code[style.name] ? prettyPrintOne(_.escape(code[style.name])) : '';
        });
      });
    }

    // Format oop params
    function formatOOP(entity, action, params, indent) {
      const info = getEntity(entity),
        arrayParams = ['groupBy', 'records'],
        newLine = "\n" + _.repeat(' ', indent),
        args = _.cloneDeep(info.class_args || []);
      let code = '\\' + info.class + '::' + action + '(';
      // Always shows implicit true permissions check for PHP
      args.push(params.checkPermissions !== false);
      code += _.map(args, phpFormat).join(', ') + ')';
      _.each(params, function(param, key) {
        let val = '';
        if (typeof objectParams[key] !== 'undefined' && key !== 'chain') {
          _.each(param, function(item, index) {
            val = phpFormat(index) + ', ' + phpFormat(item, 2 + indent);
            code += newLine + "->add" + ucfirst(key).replace(/s$/, '') + '(' + val + ')';
          });
        } else if (_.includes(arrayParams, key)) {
          _.each(param, function(item) {
            code += newLine + "->add" + ucfirst(key).replace(/s$/, '') + '(' + phpFormat(item, 2 + indent) + ')';
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
        } else if (key === 'sets') {
          _.each(param, function(set) {
            code += newLine + "->addSet(" + phpFormat(set[0]) + ', ' + formatOOP(set[1], set[2], set[3], 2 + indent);
            code += newLine + ')';
          });
        } else if (key === 'join') {
          _.each(param, function(join) {
            code += newLine + "->addJoin(" + phpFormat(join).slice(1, -1) + ')';
          });
        }
        else if (key !== 'checkPermissions') {
          code += newLine + "->set" + ucfirst(key) + '(' + phpFormat(param, 2 + indent) + ')';
        }
      });
      return code;
    }

    function isInt(value) {
      if (typeof value == 'number') {
        return true;
      }
      if (typeof value !== 'string') {
        return false;
      }
      return /^-{0,1}\d+$/.test(value);
    }

    function formatMeta(resp) {
      let ret = '';
      _.each(resp, function(val, key) {
        if (key !== 'values' && !_.isPlainObject(val) && !_.isFunction(val)) {
          ret += (ret.length ? ', ' : '') + key + ': ' + (Array.isArray(val) ? '[' + val + ']' : val);
        }
      });
      return prettyPrintOne(_.escape(ret));
    }

    $scope.execute = function() {
      $scope.status = 'info';
      $scope.loading = true;
      const apiParams = getParams();
      // This is the Api Explorer, so we always want debug info available
      apiParams.debug = true;
      $http.post(CRM.url('civicrm/ajax/api4/' + $scope.entity + '/' + $scope.action, {
        params: angular.toJson(apiParams),
        index: isInt($scope.index) ? +$scope.index : parseYaml($scope.index)
      }), null, {
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      }).then(function(resp) {
        $scope.loading = false;
        $scope.status = resp.data && resp.data.debug && resp.data.debug.log ? 'warning' : 'success';
        $scope.debug = debugFormat(resp.data);
        response = {
          meta: resp.data,
          values: resp.data.values
        };
        ctrl.formatResult();
      }, function(resp) {
        $scope.loading = false;
        $scope.status = 'danger';
        $scope.debug = debugFormat(resp.data);
        response = {
          meta: resp,
          values: resp.data
        };
        ctrl.formatResult();
      });
    };

    ctrl.formatResult = function() {
      if (!response) {
        return;
      }
      $scope.result = [formatMeta(response.meta)];
      switch (ctrl.resultFormat) {
        case 'json':
          $scope.result.push(prettyPrintOne((Array.isArray(response.values) ? '(' + response.values.length + ') ' : '') + _.escape(JSON.stringify(response.values, null, 2)), 'js', 1));
          break;

        case 'php':
          // Fields marked 'localizable' in the schema should get wrapped in ts() for the php format
          let localizable = _.pluck(_.filter(_.findWhere(getEntity().actions, {name: $scope.action}).fields, {localizable: true}), 'name') || [];
          // More field names that probably should be translated
          localizable = _.union(localizable, ['label', 'title', 'description', 'text']);
          $scope.result.push(prettyPrintOne('return ' + _.escape(phpFormat(response.values, 2, 2, localizable)) + ';', 'php', 1));
          break;
      }
    };

    function debugFormat(data) {
      const debug = data.debug ? prettyPrintOne(_.escape(JSON.stringify(data.debug, null, 2)).replace(/\\n/g, "\n")) : null;
      delete data.debug;
      return debug;
    }

    /**
     * Format value to look like php code
     */
    function phpFormat(val, indent, indentChildren, localizable) {
      if (typeof val === 'undefined') {
        return '';
      }
      if (val === null || val === true || val === false) {
        return JSON.stringify(val).toUpperCase();
      }
      let indentChild = indentChildren ? indent + indentChildren : null;
      indent = (typeof indent === 'number') ? _.repeat(' ', indent) : (indent || '');
      let ret = '',
        baseLine = indent ? indent.slice(0, -2) : '',
        newLine = indent ? '\n' : '',
        trailingComma = indent ? ',' : '';
      if ($.isPlainObject(val)) {
        if ($.isEmptyObject(val)) {
          return '[]';
        }
        $.each(val, function(k, v) {
          let ts = localizable && localizable.includes(k) && _.isString(v)  && v.length ? 'E::ts(' : '';
          let leadingComma = !ret ? '' : (newLine ? ',' : ', ');
          ret += leadingComma + newLine + indent + "'" + k + "' => " + ts + phpFormat(v, indentChild, indentChildren, localizable) + (ts ? ')' : '');
        });
        return '[' + ret + trailingComma + newLine + baseLine + ']';
      }
      if (Array.isArray(val)) {
        if (!val.length) {
          return '[]';
        }
        $.each(val, function(k, v) {
          let leadingComma = !ret ? '' : (newLine ? ',' : ', ');
          ret += leadingComma + newLine + indent + phpFormat(v, indentChild, indentChildren, localizable);
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
      str = str.replace(/\b(true|false)\b/g, match => match === "true" ? '1' : '0');
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
      const getMetaParams = {
        actions: [$scope.entity, 'getActions', {chain: {fields: [$scope.entity, 'getFields', {action: '$name'}]}}]
      };
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
      const entityInfo = getEntity(entityName);
      setHelp($scope.entity, {
        description: entityInfo.description,
        comment: entityInfo.comment,
        type: entityInfo.type,
        since: entityInfo.since,
        see: entityInfo.see
      });
    }

    if (!$scope.entity) {
      setHelp(ts('APIv4 Explorer'), {description: docs.description, comment: docs.comment, see: docs.see});
    } else if (!getEntity().actions) {
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
        $location.url('/explorer/' + newVal);
      }
    });

    // Update route when changing actions
    $scope.$watch('action', function(newVal, oldVal) {
      if ($scope.entity && $routeParams.api4action !== newVal && !_.isUndefined(newVal)) {
        $location.url('/explorer/' + $scope.entity + '/' + newVal);
      } else if (newVal) {
        setHelp($scope.entity + '::' + newVal, _.pick(_.findWhere(getEntity().actions, {name: newVal}), ['description', 'comment', 'see', 'deprecated']));
      }
    });

    $scope.paramDoc = function(name) {
      return docs.params[name];
    };

    $scope.executeDoc = function() {
      const doc = {
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

    $scope.$watch('params', writeCode, true);
    $scope.$watch('index', writeCode);
    writeCode();
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
      const ts = $scope.ts = CRM.ts(),
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
        let offset = 0;
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
        if (_.contains(clause[1], 'IS ')) {
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
        const ts = scope.ts = CRM.ts(),
          entity = $routeParams.api4entity,
          action = scope.data.action || $routeParams.api4action;
        let multi = _.includes(['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], scope.data.op);

        function destroyWidget() {
          let $el = $(element);
          if ($el.is('.crm-form-date-wrapper .crm-hidden-date')) {
            $el.crmDatepicker('destroy');
          }
          if (isSelect2()) {
            $el.crmAutocomplete('destroy');
          }
          $(element).removeData().removeAttr('type').removeAttr('placeholder').show();
        }

        function isSelect2() {
          return $(element).is('.select2-container + input');
        }

        function makeWidget(field, op) {
          const $el = $(element),
            inputType = field.input_type,
            dataType = field.data_type;
          if (!op) {
            op = field.serialize || dataType === 'Array' ? 'IN' : '=';
          }
          multi = _.includes(['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], op);
          // IS NULL, IS EMPTY, etc.
          if (_.contains(op, 'IS ')) {
            $el.hide();
            return;
          }
          if (inputType === 'Date') {
            if (_.includes(['=', '!=', '<>', '<', '>=', '<', '<='], op)) {
              $el.crmDatepicker({time: (field.input_attrs && field.input_attrs.time) || false});
            }
          } else if (_.includes(['=', '!=', '<>', 'IN', 'NOT IN'], op) && (field.fk_entity || field.options || dataType === 'Boolean')) {
           if (field.options) {
              let id = field.pseudoconstant || 'id';
              $el.addClass('loading').attr('placeholder', ts('- select -')).crmSelect2({multiple: multi, separator: "\u0001", data: [{id: '', text: ''}]});
              loadFieldOptions(field.entity || entity).then(function(data) {
                let options = _.transform(data[field.name].options, function(options, opt) {
                  options.push({id: opt[id], text: opt.label, description: opt.description, color: opt.color, icon: opt.icon});
                }, []);
                $el.removeClass('loading').crmSelect2({data: options, multiple: multi, separator: "\u0001"});
              });
            } else if (field.fk_entity) {
              $el.crmAutocomplete(field.fk_entity, {fieldName: field.entity + '.' + field.name, key: field.id_field || null}, {
                multiple: multi,
                separator: "\u0001",
                static: field.fk_entity === 'Contact' ? ['user_contact_id'] : [],
                minimumInputLength: field.fk_entity === 'Contact' ? 1 : 0
              });
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
        let parseList = function(viewValue) {
          // If the viewValue is invalid (say required but empty) it will be `undefined`
          if (typeof viewValue === 'undefined') return;

          if (!multi || !isSelect2()) {
            return viewValue;
          }

          let list = [];

          if (viewValue) {
            _.each(viewValue.split("\u0001"), function(value) {
              if (value) list.push(_.trim(value));
            });
          }

          return list;
        };

        // Copied from ng-list
        ctrl.$parsers.push(parseList);
        ctrl.$formatters.push(function(value) {
          return Array.isArray(value) ? value.join(', ') : value;
        });

        // Copied from ng-list
        ctrl.$isEmpty = function(value) {
          return !value || !value.length;
        };

        scope.$watchCollection('data', function(data) {
          destroyWidget();
          let field = getField(data.field, entity, action);
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
        const ts = scope.ts = CRM.ts();

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
          let link;
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

  angular.module('api4Explorer').component('api4ExpSet', {
    bindings: {
      set: '<',
      deleteRow: '&',
      entities: '<'
    },
    templateUrl: '~/api4Explorer/Set.html',
    controller: function($scope) {
      const ctrl = this;

      $scope.$watch('$ctrl.set[1]', function(entity) {
        if (!entity) {
          ctrl.deleteRow();
        }
      });
    }
  });

  function getEntity(entityName) {
    return _.findWhere(schema, {name: entityName});
  }

  function entityFields(entityName, action) {
    const entity = getEntity(entityName);
    if (entity && action && entity.actions) {
      return _.findWhere(entity.actions, {name: action}).fields;
    }
    return _.result(entity, 'fields');
  }

  function getExplicitJoins() {
    return _.transform(params.join, function(joins, join) {
      // Fix capitalization of AS
      join[0] = join[0].replace(/ as /i, ' AS ');
      let j = join[0].split(' AS '),
        joinEntity = _.trim(j[0]),
        joinAlias = _.trim(j[1]) || joinEntity.toLowerCase();
      joins[joinAlias] = {
        entity: joinEntity,
        alias: joinAlias,
        side: join[1] || 'LEFT',
        bridge: _.isString(join[2]) ? join[2] : null
      };
    }, {});
  }

  function getField(fieldName, entity, action) {
    let suffix = fieldName.split(':')[1];
    fieldName = fieldName.split(':')[0];
    let fieldNames = fieldName.split('.');
    let field = _.cloneDeep(get(entity, fieldNames));
    if (field && suffix) {
      field.pseudoconstant = suffix;
    }
    // When joining to a 'name' field, value fields should render an appropriate autocomplete
    if (field && field.type === 'Field' && field.name === 'name' && _.includes(fieldName, '.')) {
      field.fk_entity = field.entity;
      field.id_field = 'name';
    }
    return field;

    function get(entity, fieldNames) {
      if (fieldNames.length === 1) {
        return _.findWhere(entityFields(entity, action), {name: fieldNames[0]});
      }
      let comboName = _.findWhere(entityFields(entity, action), {name: fieldNames[0] + '.' + fieldNames[1]});
      if (comboName) {
        return comboName;
      }
      let linkName = fieldNames.shift(),
        join = getExplicitJoins()[linkName],
        newEntity = join ? join.entity : _.findWhere(entityFields(entity, action), {name: linkName}).fk_entity;
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
