(function(angular, $, _) {
  "use strict";

  // Shared between router and searchMeta service
  var searchEntity,
    searchTasks = {};

  // Declare module and route/controller/services
  angular.module('crmSearchAdmin', CRM.angRequires('crmSearchAdmin'))

    .config(function($routeProvider) {
      const ts = CRM.ts('org.civicrm.search_kit');

      $routeProvider.when('/list', {
        controller: 'searchList',
        reloadOnSearch: false,
        templateUrl: '~/crmSearchAdmin/searchListing/searchList.html',
      });
      $routeProvider.when('/create/:entity', {
        controller: 'searchCreate',
        reloadOnSearch: false,
        template: '<crm-search-admin saved-search="$ctrl.savedSearch"></crm-search-admin>',
      });
      $routeProvider.when('/edit/:id', {
        controller: 'searchEdit',
        template: '<crm-search-admin saved-search="$ctrl.savedSearch"></crm-search-admin>',
        resolve: {
          // Load saved search
          savedSearch: function($route, crmApi4) {
            var params = $route.current.params;
            return crmApi4('SavedSearch', 'get', {
              select: ['id', 'name', 'label', 'description', 'api_entity', 'api_params', 'form_values', 'is_template', 'expires_date', 'GROUP_CONCAT(DISTINCT entity_tag.tag_id) AS tag_id'],
              where: [['id', '=', params.id]],
              join: [
                ['EntityTag AS entity_tag', 'LEFT', ['entity_tag.entity_table', '=', '"civicrm_saved_search"'], ['id', '=', 'entity_tag.entity_id']],
              ],
              groupBy: ['id'],
              chain: {
                groups: ['Group', 'get', {select: ['id', 'title', 'description', 'visibility', 'group_type', 'custom.*'], where: [['saved_search_id', '=', '$id']]}],
                displays: ['SearchDisplay', 'get', {where: [['saved_search_id', '=', '$id']]}]
              }
            }, 0);
          }
        }
      });
      $routeProvider.when('/clone/:id', {
        controller: 'searchClone',
        template: '<crm-search-admin saved-search="$ctrl.savedSearch"></crm-search-admin>',
        resolve: {
          // Load saved search
          savedSearch: function($route, crmApi4) {
            var params = $route.current.params;
            return crmApi4('SavedSearch', 'get', {
              select: ['label', 'description', 'api_entity', 'api_params', 'form_values', 'is_template', 'expires_date', 'GROUP_CONCAT(DISTINCT entity_tag.tag_id) AS tag_id'],
              where: [['id', '=', params.id]],
              join: [
                ['EntityTag AS entity_tag', 'LEFT', ['entity_tag.entity_table', '=', '"civicrm_saved_search"'], ['id', '=', 'entity_tag.entity_id']],
              ],
              groupBy: ['id'],
              chain: {
                displays: ['SearchDisplay', 'get', {
                  select: ['label', 'type', 'settings'],
                  where: [['saved_search_id', '=', '$id']],
                }]
              }
            }, 0);
          }
        }
      });
    })

    // Controller for tabbed view of SavedSearches
    .controller('searchList', function($scope, $timeout, searchMeta, formatForSelect2) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = $scope.$ctrl = this;
      searchEntity = 'SavedSearch';

        // Metadata needed for filters
      this.entitySelect = searchMeta.getPrimaryAndSecondaryEntitySelect();
      this.modules = _.sortBy(_.transform((CRM.crmSearchAdmin.modules), function(modules, label, key) {
        modules.push({text: label, id: key});
      }, []), 'text');
      this.getTags = function() {
        return {results: formatForSelect2(CRM.crmSearchAdmin.tags, 'id', 'label', ['color', 'description'])};
      };

      this.getPrimaryEntities = function() {
        this.primaryEntities = _.filter(CRM.crmSearchAdmin.schema, {searchable: 'primary'});
      };

      // Tabs include a rowCount which will be updated by the search controller
      this.tabs = [
        {name: 'custom', title: ts('Custom Searches'), icon: 'fa-search-plus', rowCount: null, filters: {has_base: false}},
        {name: 'packaged', title: ts('Packaged Searches'), icon: 'fa-suitcase', rowCount: null, filters: {has_base: true}},
        {name: 'template', title: ts('Search Templates'), icon: 'fa-clipboard', rowCount: null, filters: {is_template: true}},
      ];
      $scope.$bindToRoute({
        expr: '$ctrl.tab',
        param: 'tab',
        format: 'raw'
      });
      if (!this.tab) {
        this.tab = this.tabs[0].name;
      }
      this.searchSegmentCount = null;
    })

    // Controller for creating a new search
    .controller('searchCreate', function($scope, $routeParams, $location) {
      searchEntity = $routeParams.entity;
      var ctrl = $scope.$ctrl = this;
      this.savedSearch = {
        api_entity: searchEntity,
        is_template: ($routeParams.is_template == '1'),
      };
      // Changing entity will refresh the angular page
      $scope.$watch('$ctrl.savedSearch.api_entity', function(newEntity, oldEntity) {
        if (newEntity && oldEntity && newEntity !== oldEntity) {
          $location.url('/create/' + newEntity + ($routeParams.label ? '?label=' + $routeParams.label : ''));
        }
      });
    })

    // Controller for editing a SavedSearch
    .controller('searchEdit', function($scope, savedSearch) {
      searchEntity = savedSearch.api_entity;
      this.savedSearch = savedSearch;
      $scope.$ctrl = this;
    })

    // Controller for cloning a SavedSearch
    .controller('searchClone', function($scope, $routeParams, savedSearch) {
      const makeTemplate = ($routeParams.is_template == '1');
      searchEntity = savedSearch.api_entity;
      // When cloning a search or a template as-is, append 'copy' to the label
      if (savedSearch.is_template === makeTemplate) {
        savedSearch.label += ' ' + ts('(copy)');
      }
      // When making a new search from a template, delete label
      else if (!makeTemplate) {
        savedSearch.label = '';
      }
      delete savedSearch.id;
      savedSearch.displays.forEach(display => {
        delete display.id;
        display.acl_bypass = false;
        if (savedSearch.is_template === makeTemplate) {
          display.label += ' ' + ts('(copy)');
        }
      });
      savedSearch.is_template = makeTemplate;
      this.savedSearch = savedSearch;
      $scope.$ctrl = this;
    })

    .factory('searchMeta', function($q, crmApi4, formatForSelect2) {
      function getEntity(entityName) {
        if (entityName) {
          return _.find(CRM.crmSearchAdmin.schema, {name: entityName});
        }
      }
      // Get join metadata matching a given expression like "Email AS Contact_Email_contact_id_01"
      function getJoin(savedSearch, fullNameOrAlias) {
        var alias = _.last(fullNameOrAlias.split(' AS ')),
          path = alias,
          baseEntity = searchEntity,
          labels = [],
          join,
          result;
        while (path.length) {
          /* jshint -W083 */
          join = _.find(CRM.crmSearchAdmin.joins[baseEntity], function(join) {
            return new RegExp('^' + join.alias + '_\\d\\d').test(path);
          });
          if (!join) {
            return;
          }
          path = path.replace(join.alias + '_', '');
          var num = parseInt(path.substr(0, 2), 10);
          labels.push(join.label + (num > 1 ? ' ' + num : ''));
          path = path.replace(/^\d\d_?/, '');
          if (path.length) {
            baseEntity = join.entity;
          }
        }
        const defaultLabel = labels.join(' - ');
        result = _.assign(_.cloneDeep(join), {
          label: (savedSearch && savedSearch.form_values && savedSearch.form_values.join && savedSearch.form_values.join[alias]) || defaultLabel,
          defaultLabel: defaultLabel,
          alias: alias,
          baseEntity: baseEntity,
          icon: getEntity(join.entity).icon,
        });
        // Add the numbered suffix to the join conditions
        // If this is a deep join, also add the base entity prefix
        var prefix = alias.replace(new RegExp('_?' + join.alias + '_?\\d?\\d?$'), '');
        function replaceRefs(condition) {
          if (_.isArray(condition)) {
            _.each(condition, function(ref, side) {
              if (side !== 1 && typeof ref === 'string') {
                if (_.includes(ref, '.')) {
                  condition[side] = ref.replace(join.alias + '.', alias + '.');
                } else if (prefix.length && !_.includes(ref, '"') && !_.includes(ref, "'")) {
                  condition[side] = prefix + '.' + ref;
                }
              }
            });
          }
        }
        _.each(result.conditions, replaceRefs);
        _.each(result.defaults, replaceRefs);
        return result;
      }
      function getFieldAndJoin(fieldName, entityName) {
        var fieldPath = fieldName.split(':')[0],
          dotSplit = fieldPath.split('.'),
          name,
          join,
          field;
        // If 2 or more segments, the first might be the name of a join
        if (dotSplit.length > 1) {
          join = getJoin(null, dotSplit[0]);
          if (join) {
            dotSplit.shift();
            entityName = join.entity;
          }
        }
        name = dotSplit.join('.');
        field = _.find(getEntity(entityName).fields, {name: name});
        if (!field && join && join.bridge) {
          field = _.find(getEntity(join.bridge).fields, {name: name});
        }
        // Might be a pseudoField
        if (!field) {
          field = _.find(CRM.crmSearchAdmin.pseudoFields, {name: name});
        }
        if (field) {
          field.baseEntity = entityName;
        }
        return {field: field, join: join};
      }
      function parseFnArgs(info, expr) {
        var matches = /([_A-Z]*)\((.*)\)(:[a-z]+)?$/.exec(expr),
          fnName = matches[1],
          argString = matches[2];
        info.fn = _.find(CRM.crmSearchAdmin.functions, {name: fnName || 'e'});
        info.data_type = (info.fn && info.fn.data_type) || null;
        info.suffix = matches[3];

        function getKeyword(whitelist) {
          var keyword;
          _.each(_.filter(whitelist), function(flag) {
            if (argString.indexOf(flag + ' ') === 0 || argString === flag) {
              keyword = flag;
              argString = _.trim(argString.substr(flag.length));
              return false;
            }
          });
          return keyword;
        }

        function getExpr() {
          var expr;
          if (argString.indexOf('"') === 0) {
            // Match double-quoted string
            expr = argString.match(/"([^"\\]|\\.)*"/)[0];
          } else if (argString.indexOf("'") === 0) {
            // Match single-quoted string
            expr = argString.match(/'([^'\\]|\\.)*'/)[0];
          } else {
            // Match anything else
            expr = argString.match(/[^ ,]+/)[0];
          }
          if (expr) {
            argString = _.trim(argString.substr(expr.length));
            return parseArg(expr);
          }
        }

        _.each(info.fn.params, function(param, index) {
          var exprCount = 0,
            expr, flagBefore;
          argString = _.trim(argString);
          if (!argString.length || (param.name && !_.startsWith(argString, param.name + ' '))) {
            return false;
          }
          if (param.max_expr) {
            while (++exprCount <= param.max_expr && argString.length) {
              flagBefore = getKeyword(_.keys(param.flag_before || {}));
              var name = getKeyword(param.name ? [param.name] : []);
              expr = getExpr();
              if (expr) {
                expr.param = param.name || index;
                expr.flag_before = flagBefore;
                expr.name = name;
                info.args.push(expr);
              }
              // Only continue if an expression was found and followed by a comma
              if (!expr) {
                break;
              }
              getKeyword([',']);
            }
            if (info.args.length && !_.isEmpty(param.flag_after)) {
              _.last(info.args).flag_after = getKeyword(_.keys(param.flag_after));
            }
          } else if (param.flag_before && !param.optional) {
            flagBefore = getKeyword(_.keys(param.flag_before));
            info.args.push({
              value: '',
              flag_before: flagBefore
            });
          }
        });
        if (!info.data_type && info.args.length) {
          info.data_type = info.args[0].data_type;
        }
      }
      // @param {String} arg
      function parseArg(arg) {
        arg = _.trim(arg);
        if (arg && !isNaN(arg)) {
          return {
            type: 'number',
            data_type: Number.isInteger(+arg) ? 'Integer' : 'Float',
            value: +arg
          };
        } else if (_.includes(['"', "'"], arg.substr(0, 1))) {
          return {
            type: 'string',
            data_type: 'String',
            value: arg.substr(1, arg.length - 2)
          };
        } else if (arg) {
          var fieldAndJoin = getFieldAndJoin(arg, searchEntity);
          if (fieldAndJoin.field) {
            var split = arg.split(':'),
              prefixPos = split[0].lastIndexOf(fieldAndJoin.field.name);
            return {
              type: 'field',
              value: arg,
              path: split[0],
              field: fieldAndJoin.field,
              data_type: fieldAndJoin.field.data_type,
              join: fieldAndJoin.join,
              prefix: prefixPos > 0 ? split[0].substring(0, prefixPos) : '',
              suffix: !split[1] ? '' : ':' + split[1]
            };
          }
        }
      }
      function parseExpr(expr) {
        if (!expr) {
          return;
        }
        var splitAs = expr.split(' AS '),
          info = {fn: null, args: [], alias: _.last(splitAs), data_type: null},
          bracketPos = expr.indexOf('(');
        if (bracketPos >= 0 && !_.findWhere(CRM.crmSearchAdmin.pseudoFields, {name: expr})) {
          parseFnArgs(info, splitAs[0]);
        } else {
          var arg = parseArg(splitAs[0]);
          if (arg) {
            arg.param = 0;
            info.data_type = arg.data_type;
            info.args.push(arg);
          }
        }
        return info;
      }
      function getDefaultLabel(col, savedSearch) {
        var info = parseExpr(col),
          label = '';
        if (info.fn) {
          label = '(' + info.fn.title + ')';
        }
        _.each(info.args, function(arg) {
          if (arg.join) {
            let join = getJoin(savedSearch, arg.join.alias);
            label += (label ? ' ' : '') + join.label + ':';
          }
          if (arg.field) {
            label += (label ? ' ' : '') + arg.field.label;
          } else {
            label += (label ? ' ' : '') + arg.value;
          }
        });
        return label;
      }
      function fieldToColumn(fieldExpr, defaults, savedSearch) {
        var info = parseExpr(fieldExpr),
          field = (_.findWhere(info.args, {type: 'field'}) || {}).field || {},
          values = _.merge({
            type: field.input_type === 'RichTextEditor' ? 'html' : 'field',
            key: info.alias,
            dataType: (info.fn && info.fn.data_type) || field.data_type
          }, defaults);
        if (defaults.label === true) {
          values.label = getDefaultLabel(fieldExpr, savedSearch);
        }
        if (defaults.sortable) {
          values.sortable = field.type && field.type !== 'Pseudo';
        }
        return values;
      }
      return {
        getEntity: getEntity,
        getBaseEntity: function() {
          return getEntity(searchEntity);
        },
        getField: function(fieldName, entityName) {
          return getFieldAndJoin(fieldName, entityName || searchEntity).field;
        },
        getJoin: getJoin,
        parseExpr: parseExpr,
        getDefaultLabel: getDefaultLabel,
        fieldToColumn: fieldToColumn,
        getSearchTasks: function(entityName) {
          if (!(entityName in searchTasks)) {
            searchTasks[entityName] = crmApi4('SearchDisplay', 'getSearchTasks', {
              savedSearch: {api_entity: entityName}
            });
          }
          return searchTasks[entityName];
        },
        // Supply default aggregate function appropriate to the data_type
        getDefaultAggregateFn: function(info, apiParams) {
          let arg = info.args[0] || {};
          if (arg.suffix) {
            return null;
          }
          let groupByFn;
          if (apiParams.groupBy) {
            apiParams.groupBy.forEach(function(groupBy) {
              let expr = parseExpr(groupBy);
              if (expr && expr.fn && expr.args) {
                let paths = expr.args.map(ex => ex.path);
                if (paths.includes(arg.path)) {
                  groupByFn = expr.fn.name;
                }
              }
            });
          }
          if (groupByFn) {
            return groupByFn;
          }
          switch (info.data_type) {
            case 'Integer':
              // For the `id` field, default to COUNT, otherwise SUM
              return (!info.fn && arg.field && arg.field.name === 'id') ? 'COUNT' : 'SUM';

            case 'Float':
            case 'Money':
              return 'SUM';
          }
          return null;
        },
        // Find all possible search columns that could serve as contact_id for a smart group
        getSmartGroupColumns: function(savedSearch) {
          var joins = _.pluck((savedSearch.api_params.join || []), 0);
          return _.transform([savedSearch.api_entity].concat(joins), function(columns, joinExpr) {
            var joinName = joinExpr.split(' AS '),
              joinInfo = joinName[1] ? getJoin(savedSearch, joinName[1]) : {entity: joinName[0]},
              entity = getEntity(joinInfo.entity),
              prefix = joinInfo.alias ? joinInfo.alias + '.' : '';
            _.each(entity.fields, function(field) {
              if (['Contact', 'Individual', 'Household', 'Organization'].includes(entity.name) && field.name === 'id' || field.fk_entity === 'Contact') {
                columns.push({
                  id: prefix + field.name,
                  text: (joinInfo.label ? joinInfo.label + ': ' : '') + field.label,
                  icon: entity.icon
                });
              }
            });
          });
        },
        // Ensure option lists are loaded for all fields with options
        // Sets an optionsLoaded property on each entity to avoid duplicate requests
        loadFieldOptions: function(entities) {
          var entitiesToLoad = _.transform(entities, function(entitiesToLoad, entityName) {
            var entity = getEntity(entityName);
            if (!('optionsLoaded' in entity)) {
              entity.optionsLoaded = false;
              entitiesToLoad[entityName] = [entityName, 'getFields', {
                loadOptions: ['id', 'name', 'label', 'description', 'color', 'icon'],
                // For fields with both an FK and an option list, prefer the FK
                // because it's more efficient to render an autocomplete than to
                // pre-load potentially thousands of options into a select dropdown.
                where: [['options', '!=', false], ['suffixes', 'CONTAINS', 'name']],
                select: ['options']
              }, {name: 'options'}];
            }
          }, {});
          if (!_.isEmpty(entitiesToLoad)) {
            crmApi4(entitiesToLoad).then(function(results) {
              _.each(results, function(fields, entityName) {
                var entity = getEntity(entityName);
                _.each(fields, function(options, fieldName) {
                  var field = _.find(entity.fields, {name: fieldName});
                  if (field) {
                    field.options = options;
                  }
                });
                entity.optionsLoaded = true;
              });
            });
          }
        },
        pickIcon: function() {
          var deferred = $q.defer();
          $('#crm-search-admin-icon-picker').off('change').siblings('.crm-icon-picker-button').click();
          $('#crm-search-admin-icon-picker').on('change', function() {
            deferred.resolve($(this).val());
          });
          return deferred.promise;
        },
        // Returns name of explicit or implicit join, for links
        getJoinEntity: function(info) {
          var arg = _.findWhere(info.args, {type: 'field'}) || {},
            field = arg.field || {};
          if (field.fk_entity || field.name !== field.fieldName) {
            return arg.prefix + (field.fk_entity ? field.name : field.name.substr(0, field.name.lastIndexOf('.')));
          } else if (arg.prefix) {
            return arg.prefix.replace('.', '');
          }
          return '';
        },
        getPrimaryAndSecondaryEntitySelect: function() {
          var primaryEntities = _.filter(CRM.crmSearchAdmin.schema, {searchable: 'primary'}),
            secondaryEntities = _.filter(CRM.crmSearchAdmin.schema, {searchable: 'secondary'}),
            select = formatForSelect2(primaryEntities, 'name', 'title_plural', ['description', 'icon']);
          select.push({
            text: ts('More...'),
            description: ts('Other less-commonly searched entities'),
            children: formatForSelect2(secondaryEntities, 'name', 'title_plural', ['description', 'icon'])
          });
          return select;
        }
      };
    })
    .directive('contenteditable', function() {
      return {
        require: 'ngModel',
        link: function(scope, elm, attrs, ctrl) {
          // view -> model
          elm.on('blur', function() {
            ctrl.$setViewValue(elm.html());
          });

          // model -> view
          ctrl.$render = function() {
            elm.html(ctrl.$viewValue);
          };
        }
      };
    });

  // Shoehorn in a non-angular widget for picking icons
  $(function() {
    $('#crm-container').append('<div style="display:none"><input id="crm-search-admin-icon-picker" title="' + ts('Icon Picker') + '"></div>');
    CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmIconPicker.js').then(function() {
      $('#crm-search-admin-icon-picker').crmIconPicker();
    });
  });

})(angular, CRM.$, CRM._);
