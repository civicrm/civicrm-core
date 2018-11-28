(function(angular, $, _, undefined) {

  // Cache schema metadata
  var schema = [];
  // Cache fk schema data
  var links = [];
  // Cache list of entities
  var entities = [];
  // Cache list of actions
  var actions = [];

  angular.module('api4').config(function($routeProvider) {
      $routeProvider.when('/api4/:api4entity?/:api4action?', {
        controller: 'Api4Explorer',
        templateUrl: '~/api4/Explorer.html',
        reloadOnSearch: false
      });
    }
  );

  angular.module('api4').controller('Api4Explorer', function($scope, $routeParams, $location, $timeout, crmUiHelp, crmApi4) {
    var ts = $scope.ts = CRM.ts('api4');
    $scope.entities = entities;
    $scope.operators = arrayToSelect2(CRM.vars.api4.operators);
    $scope.actions = actions;
    $scope.fields = [];
    $scope.fieldsAndJoins = [];
    $scope.availableParams = {};
    $scope.params = {};
    var getMetaParams = schema.length ? {} : {schema: ['Entity', 'getFields'], links: ['Entity', 'getLinks']},
      objectParams = {orderBy: 'ASC', values: ''},
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
      javascript: ''
    };

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

    function entityFields(entity) {
      return _.result(_.findWhere(schema, {name: entity}), 'fields');
    }

    function getFieldList() {
      var fields = [];
      formatForSelect2(entityFields($scope.entity), fields, 'name', ['description', 'required', 'default_value']);
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

    $scope.valuesFields = function() {
      var fields = [];
      _.each(_.cloneDeep($scope.fields), function(field, index) {
        if ((field.id === 'id' && $scope.action === 'create') || field.children) {
          return;
        }
        if ($scope.params.values && typeof $scope.params.values[field.id] !== 'undefined') {
          field.disabled = true;
        }
        fields.push(field);
      });
      return fields;
    };

    $scope.selectOptions = function() {
      if ($scope.availableParams.select.options) {
        return arrayToSelect2($scope.availableParams.select.options);
      } else {
        return $scope.fieldsAndJoins;
      }
    };

    $scope.formatSelect2Item = function(row) {
      return _.escape(row.text) +
        (isFieldRequiredForCreate(row) ? '<span class="crm-marker"> *</span>' : '') +
        (row.description ? '<div class="crm-select2-row-description"><p>' + _.escape(row.description) + '</p></div>' : '');
    };

    function isFieldRequiredForCreate(field) {
      return field.required && !field.default_value;
    }

    // Get all params that have been set
    function getParams() {
      var params = {};
      _.each($scope.params, function(param, key) {
        if (param != $scope.availableParams[key].default && !(typeof param === 'object' && _.isEmpty(param))) {
          params[key] = param;
        }
      });
      _.each(objectParams, function(defaultVal, key) {
        if (params[key]) {
          var newParam = {};
          _.each(params[key], function(item) {
            newParam[item[0]] = item[1];
          });
          params[key] = newParam;
        }
      });
      return params;
    }

    function selectAction() {
      $scope.action = $routeParams.api4action;
      $scope.fields = getFieldList();
      $scope.fieldsAndJoins = addJoins($scope.fields);
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
            if (name == 'limit') {
              defaultVal = 25;
            }
            $scope.$bindToRoute({
              expr: 'params["' + name + '"]',
              param: name,
              format: format,
              default: defaultVal,
              deep: name === 'where'
            });
          }
          if (typeof objectParams[name] !== 'undefined') {
            $scope.$watch('params.' + name, function(values) {
              // Remove empty values
              _.each(values, function(clause, index) {
                if (!clause[0]) {
                  $scope.params[name].splice(index, 1);
                }
              });
            }, true);
            $scope.$watch('controls.' + name, function(value) {
              var field = value;
              $timeout(function() {
                if (field) {
                  var defaultOp = objectParams[name];
                  if (_.isEmpty($scope.params[name])) {
                    $scope.params[name] = [[field, defaultOp]];
                  } else {
                    $scope.params[name].push([field, defaultOp]);
                  }
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

    function stringify(value, trim) {
      var str = JSON.stringify(value).replace(/,/g, ', ');
      if (trim) {
        str = str.slice(1, -1);
      }
      return str.trim();
    }

    function writeCode() {
      var code = {
        php: ts('Select an entity and action'),
        javascript: ''
      },
        entity = $scope.entity,
        action = $scope.action,
        params = getParams(),
        result = 'result';
      if ($scope.entity && $scope.action) {
        if (action.slice(0, 3) === 'get') {
          result = lcfirst(action.replace(/s$/, '').slice(3) || entity);
        }
        var results = lcfirst(pluralize(result)),
          paramCount = _.size(params),
          i = 0;
        code.javascript = "CRM.api4('" + entity + "', '" + action + "', {";
        _.each(params, function(param, key) {
          code.javascript += "\n  " + key + ': ' + stringify(param) +
            (++i < paramCount ? ',' : '');
          if (key === 'checkPermissions') {
            code.javascript += ' // IGNORED: permissions are always enforced from client-side requests';
          }
        });
        code.javascript += "\n}).done(function(" + results + ") {\n  // do something with " + results + " array\n});";
        if (entity.substr(0, 7) !== 'Custom_') {
          code.php = '$' + results + " = \\Civi\\Api4\\" + entity + '::' + action + '()';
        } else {
          code.php = '$' + results + " = \\Civi\\Api4\\CustomValue()::" + action + "('" + entity.substr(7) + "')";
        }
        _.each(params, function(param, key) {
          var val = '';
          if (typeof objectParams[key] !== 'undefined') {
            _.each(param, function(item, index) {
              val = stringify(index) + ', ' + stringify(item);
              code.php += "\n  ->add" + ucfirst(key).replace(/s$/, '') + '(' + val + ')';
            });
          } else if (key === 'where') {
            _.each(param, function (clause) {
              if (clause[0] === 'AND' || clause[0] === 'OR' || clause[0] === 'NOT') {
                code.php += "\n  ->addClause('" + clause[0] + "', " + stringify(clause[1], true) + ')';
              } else {
                code.php += "\n  ->addWhere(" + stringify(clause, true) + ")";
              }
            });
          } else {
            code.php += "\n  ->set" + ucfirst(key) + '(' + stringify(param) + ')';
          }
        });
        code.php += "\n  ->execute();\nforeach ($" + results + ' as $' + result + ') {\n  // do something\n}';
      }
      $scope.code = code;
    }

    $scope.execute = function() {
      $scope.status = 'warning';
      $scope.loading = true;
      crmApi4($scope.entity, $scope.action, getParams())
        .then(function(data) {
          var meta = {length: data.length},
            result = JSON.stringify(data, null, 2);
          data.length = 0;
          _.assign(meta, data);
          $scope.loading = false;
          $scope.status = 'success';
          $scope.result = [JSON.stringify(meta).replace('{', '').replace(/}$/, ''), result];
        }, function(data) {
          $scope.loading = false;
          $scope.status = 'danger';
          $scope.result = [JSON.stringify(data, null, 2)];
        });
    };

    function fetchMeta() {
      crmApi4(getMetaParams)
        .then(function(data) {
          if (data.schema) {
            schema = data.schema;
            entities.length = 0;
            formatForSelect2(schema, entities, 'name', ['description']);
            if ($scope.entity && !$scope.action) {
              showEntityHelp($scope.entity);
            }
          }
          if (data.links) {
            links = data.links;
          }
          if (data.actions) {
            formatForSelect2(data.actions, actions, 'name', ['description', 'params']);
            selectAction();
          }
        });
    }

    // Help for an entity with no action selected
    function showEntityHelp(entity) {
      var entityInfo = _.findWhere(schema, {name: entity});
      $scope.helpTitle = helpTitle = $scope.entity;
      $scope.helpContent = helpContent = {
        description: entityInfo.description,
        comment: entityInfo.comment
      };
    }

    if (!$scope.entity) {
      $scope.helpTitle = helpTitle = ts('Help');
      $scope.helpContent = helpContent = {description: ts('Welcome to the api explorer.'), comment: ts('Select an entity to begin.')};
      if (getMetaParams.schema) {
        fetchMeta();
      }
    } else if (!actions.length) {
      if (getMetaParams.schema) {
        entities.push({id: $scope.entity, text: $scope.entity});
      }
      getMetaParams.actions = [$scope.entity, 'getActions'];
      fetchMeta();
    } else {
      selectAction();
    }

    if ($scope.entity && schema.length) {
      showEntityHelp($scope.entity);
    }

    // Update route when changing entity
    $scope.$watch('entity', function(newVal, oldVal) {
      if (oldVal !== newVal) {
        // Flush actions cache to re-fetch for new entity
        actions = [];
        $location.url('/api4/' + newVal);
      }
    });

    // Update route when changing actions
    $scope.$watch('action', function(newVal, oldVal) {
      if ($scope.entity && $routeParams.api4action !== newVal && !_.isUndefined(newVal)) {
        $location.url('/api4/' + $scope.entity + '/' + newVal);
      } else if (newVal) {
        $scope.helpTitle = helpTitle = $scope.entity + '::' + newVal;
        $scope.helpContent = helpContent = _.pick(_.findWhere(actions, {id: newVal}), ['description', 'comment']);
      }
    });

    $scope.$watch('params', writeCode, true);
    writeCode();

  });

  angular.module('api4').directive('crmApi4WhereClause', function($timeout) {
    return {
      scope: {
        data: '=crmApi4WhereClause'
      },
      templateUrl: '~/api4/WhereClause.html',
      link: function (scope, element, attrs) {
        var ts = scope.ts = CRM.ts('api4');
        scope.newClause = '';
        scope.conjunctions = ['AND', 'OR', 'NOT'];

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
