(function(angular, $, _) {
  "use strict";

  // Shared between router and searchMeta service
  let searchEntity,
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
            const params = $route.current.params;
            return crmApi4('SavedSearch', 'get', {
              select: ['id', 'name', 'label', 'description', 'api_entity', 'api_params', 'form_values', 'is_template', 'expires_date', 'GROUP_CONCAT(DISTINCT entity_tag.tag_id) AS tag_id'],
              where: [['id', '=', params.id]],
              join: [
                ['EntityTag AS entity_tag', 'LEFT', ['entity_tag.entity_table', '=', '"civicrm_saved_search"'], ['id', '=', 'entity_tag.entity_id']],
              ],
              groupBy: ['id'],
              chain: {
                groups: ['Group', 'get', {select: ['id', 'title', 'description', 'visibility', 'group_type', 'custom.*'], where: [['saved_search_id', '=', '$id']]}],
                displays: ['SearchDisplay', 'get', {
                  select: ['*', 'is_autocomplete_default'],
                  where: [['saved_search_id', '=', '$id']],
                }]
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
            const params = $route.current.params;
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
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = $scope.$ctrl = this;
      searchEntity = 'SavedSearch';

      // Metadata needed for filters
      this.entitySelect = searchMeta.getPrimaryAndSecondaryEntitySelect();
      this.modules = Object.entries(CRM.crmSearchAdmin.modules).map(([key, label]) => ({
        text: label,
        id: key
      })).sort((a, b) => a.text.localeCompare(b.text));

      this.getTags = function() {
        return {results: formatForSelect2(CRM.crmSearchAdmin.tags, 'id', 'label', ['color', 'description'])};
      };

      this.getPrimaryEntities = function() {
        this.primaryEntities = CRM.crmSearchAdmin.schema.filter(entity => entity.searchable === 'primary');
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
      const ctrl = $scope.$ctrl = this;
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

    .factory('searchMeta', function($q, crmApi4, formatForSelect2, md5) {
      function getEntity(entityName) {
        if (entityName) {
          return CRM.crmSearchAdmin.schema.find(entity => entity.name === entityName);
        }
      }
      // Get join metadata matching a given expression like "Email AS Contact_Email_contact_id_01"
      function getJoin(savedSearch, fullNameOrAlias) {
        const alias = fullNameOrAlias.split(' AS ').at(-1);
        let path = alias;
        let baseEntity = savedSearch?.api_entity || searchEntity;
        const labels = [];
        let join;
        let result;
        while (path.length) {
          /* jshint -W083 */
          join = CRM.crmSearchAdmin.joins[baseEntity].find(join =>
            new RegExp('^' + join.alias + '_\\d\\d').test(path)
          );
          if (!join) {
            return;
          }
          path = path.replace(join.alias + '_', '');
          let num = parseInt(path.substr(0, 2), 10);
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
        let prefix = alias.replace(new RegExp('_?' + join.alias + '_?\\d?\\d?$'), '');
        function replaceRefs(condition) {
          if (Array.isArray(condition)) {
            condition.forEach((ref, side) => {
              if (side !== 1 && typeof ref === 'string') {
                if (ref.includes('.')) {
                  condition[side] = ref.replace(join.alias + '.', alias + '.');
                } else if (prefix.length && !ref.includes('"') && !ref.includes("'")) {
                  condition[side] = prefix + '.' + ref;
                }
              }
            });
          }
        }
        (result.conditions ?? []).forEach(replaceRefs);
        (result.defaults ?? []).forEach(replaceRefs);
        return result;
      }
      function getFieldAndJoin(fieldName, entityName) {
        const fieldPath = fieldName.split(':')[0];
        const dotSplit = fieldPath.split('.');
        let name;
        let join;
        let field;
        // If 2 or more segments, the first might be the name of a join
        if (dotSplit.length > 1) {
          join = getJoin({api_entity: entityName}, dotSplit[0]);
          if (join) {
            dotSplit.shift();
            entityName = join.entity;
          }
        }
        name = dotSplit.join('.');
        field = getEntity(entityName).fields.find(f => f.name === name);
        if (!field && join && join.bridge) {
          field = getEntity(join.bridge).fields.find(f => f.name === name);
        }
        // Might be a pseudoField
        if (!field) {
          field = CRM.crmSearchAdmin.pseudoFields.find(f => f.name === name);
        }
        if (field) {
          field.baseEntity = entityName;
        }
        return {field: field, join: join};
      }
      function parseFnArgs(info, expr) {
        const matches = /([_A-Z]*)\((.*)\)(:[a-z]+)?$/.exec(expr),
          fnName = matches[1];
        let argString = matches[2];
        info.fn = CRM.crmSearchAdmin.functions.find(fn => fn.name === (fnName || 'e'));
        info.data_type = info.fn?.data_type || null;
        info.suffix = matches[3];

        function getKeyword(whitelist) {
          let keyword;
          whitelist.filter(Boolean).forEach(flag => {
            if (argString.indexOf(flag + ' ') === 0 || argString.indexOf(flag + ',') === 0 || argString === flag) {
              keyword = flag;
              argString = argString.substr(flag.length).trim();
              return false;
            }
          });
          return keyword;
        }

        function getExpr() {
          let expr;
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
            argString = argString.slice(expr.length).trim();
            return parseArg(expr);
          }
        }

        info.fn.params.forEach((param, index) => {
          let exprCount = 0,
            expr, flagBefore;
          argString = argString.trim();
          if (!argString.length || (param.name && !argString.startsWith(param.name + ' '))) {
            return false;
          }
          if (param.max_expr) {
            while (++exprCount <= param.max_expr && argString.length) {
              flagBefore = getKeyword(Object.keys(param.flag_before || {}));
              let name = getKeyword(param.name ? [param.name] : []);
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
            if (info.args.length && Object.keys(param.flag_after || {}).length > 0) {
              info.args[info.args.length - 1].flag_after = getKeyword(Object.keys(param.flag_after));
            }
          } else if (param.flag_before && !param.optional) {
            flagBefore = getKeyword(Object.keys(param.flag_before));
            info.args.push({
              value: '',
              flag_before: flagBefore
            });
            // Tee up the next param
            getKeyword([',']);
          }
        });
        if (!info.data_type && info.args.length) {
          info.data_type = info.args[0].data_type;
        }
      }
      // @param {String} arg
      function parseArg(arg) {
        arg = arg.trim();
        if (arg && !isNaN(arg)) {
          return {
            type: 'number',
            data_type: Number.isInteger(+arg) ? 'Integer' : 'Float',
            value: +arg
          };
        } else if (['"', "'"].includes(arg.slice(0, 1))) {
          return {
            type: 'string',
            data_type: 'String',
            value: arg.slice(1, -1)
          };
        } else if (arg) {
          const fieldAndJoin = getFieldAndJoin(arg, searchEntity);
          if (fieldAndJoin.field) {
            const split = arg.split(':'),
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
        const splitAs = expr.split(' AS ', 2);
        const info = {fn: null, args: [], alias: splitAs[splitAs.length - 1], data_type: null};
        if (expr.includes('(') && !CRM.crmSearchAdmin.pseudoFields.find((field) => field.name === expr)) {
          parseFnArgs(info, splitAs[0]);
          return info;
        }
        const arg = parseArg(splitAs[0]);
        if (arg) {
          arg.param = 0;
          info.data_type = arg.data_type;
          info.args.push(arg);
        }
        return info;
      }
      function getDefaultLabel(col, savedSearch) {
        const info = parseExpr(col);
        let label = '';
        if (info.fn) {
          label = '(' + info.fn.title + ')';
        }
        info.args.forEach(arg => {
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
        const info = parseExpr(fieldExpr);
        const field = (info.args.find(arg => arg.type === 'field') || {}).field || {};
        const values = Object.assign({
          type: field.input_type === 'RichTextEditor' ? 'html' : 'field',
          key: info.alias,
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
        createSqlName: function(key) {
          // Generate a preview of the default SQL column-names that would be generated by the server.
          // WARNING: This formula lives in both Civi\Search\Meta and crmSearchAdmin.module.js. Keep synchronized!
          let [name] = key.split(':');
          if (name.length <= 58) {
            return CRM.utils.munge(name, '_');
          } else {
            return CRM.utils.munge(name, '_', 42) + (md5(name)).substring(0, 16);
          }
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
          const joins = (savedSearch.api_params.join || []).map(j => j[0]);
          return [savedSearch.api_entity].concat(joins).reduce((columns, joinExpr) => {
            const joinName = joinExpr.split(' AS ');
            const joinInfo = joinName[1] ? getJoin(savedSearch, joinName[1]) : {entity: joinName[0]};
            const entity = getEntity(joinInfo.entity);
            const prefix = joinInfo.alias ? joinInfo.alias + '.' : '';
            entity?.fields?.forEach(field => {
              if (['Contact', 'Individual', 'Household', 'Organization'].includes(entity.name) && field.name === 'id' || field.fk_entity === 'Contact') {
                columns.push({
                  id: prefix + field.name,
                  text: (joinInfo.label ? joinInfo.label + ': ' : '') + field.label,
                  icon: entity.icon
                });
              }
            });
            return columns;
          }, []);
        },
        // Ensure option lists are loaded for all fields with options
        // Sets an optionsLoaded property on each entity to avoid duplicate requests
        loadFieldOptions: function(entities) {
          const entitiesToLoad = entities.reduce((entitiesToLoad, entityName) => {
            const entity = getEntity(entityName);
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
            return entitiesToLoad;
          }, {});
          if (Object.keys(entitiesToLoad).length > 0) {
            crmApi4(entitiesToLoad).then((results) => {
              Object.entries(results).forEach(([entityName, fields]) => {
                const entity = getEntity(entityName);
                Object.entries(fields).forEach(([fieldName, options]) => {
                  const field = entity.fields.find(f => f.name === fieldName);
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
          const deferred = $q.defer();
          $('#crm-search-admin-icon-picker').off('change').siblings('.crm-icon-picker-button').click();
          $('#crm-search-admin-icon-picker').on('change', function() {
            deferred.resolve($(this).val());
          });
          return deferred.promise;
        },
        // Returns name of explicit or implicit join, for links
        getJoinEntity: function(info) {
          const arg = info.args.find(arg => arg.type === 'field') || {};
          const field = arg.field || {};
          if (field.fk_entity || field.name !== field.fieldName) {
            return arg.prefix + (field.fk_entity ? field.name : field.name.slice(0, field.name.lastIndexOf('.')));
          } else if (arg.prefix) {
            return arg.prefix.replace('.', '');
          }
          return '';
        },
        getPrimaryAndSecondaryEntitySelect: function() {
          const primaryEntities = CRM.crmSearchAdmin.schema.filter(entity => entity.searchable === 'primary');
          const secondaryEntities = CRM.crmSearchAdmin.schema.filter(entity => entity.searchable === 'secondary');
          const select = formatForSelect2(primaryEntities, 'name', 'title_plural', ['description', 'icon']);
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
