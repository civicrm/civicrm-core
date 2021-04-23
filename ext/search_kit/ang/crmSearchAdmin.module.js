(function(angular, $, _) {
  "use strict";

  // Shared between router and searchMeta service
  var searchEntity,
    joinIndex,
    undefined;

  // Declare module and route/controller/services
  angular.module('crmSearchAdmin', CRM.angRequires('crmSearchAdmin'))

    .config(function($routeProvider) {
      $routeProvider.when('/list', {
        controller: 'searchList',
        templateUrl: '~/crmSearchAdmin/searchList.html',
        resolve: {
          // Load data for lists
          savedSearches: function(crmApi4) {
            return crmApi4('SavedSearch', 'get', {
              select: [
                'id',
                'name',
                'label',
                'api_entity',
                'api_params',
                'created.display_name',
                'modified.display_name',
                'created_date',
                'modified_date',
                'GROUP_CONCAT(display.name ORDER BY display.id) AS display_name',
                'GROUP_CONCAT(display.label ORDER BY display.id) AS display_label',
                'GROUP_CONCAT(display.type:icon ORDER BY display.id) AS display_icon',
                'GROUP_CONCAT(DISTINCT group.title) AS groups'
              ],
              join: [['SearchDisplay AS display'], ['Group AS group']],
              where: [['api_entity', 'IS NOT NULL']],
              groupBy: ['id']
            });
          }
        }
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
              where: [['id', '=', params.id]],
              chain: {
                groups: ['Group', 'get', {select: ['id', 'title', 'description', 'visibility', 'group_type', 'custom.*'], where: [['saved_search_id', '=', '$id']]}],
                displays: ['SearchDisplay', 'get', {where: [['saved_search_id', '=', '$id']]}]
              }
            }, 0);
          }
        }
      });
    })

    // Controller for creating a new search
    .controller('searchCreate', function($scope, $routeParams, $location) {
      searchEntity = $routeParams.entity;
      $scope.$ctrl = this;
      this.savedSearch = {
        api_entity: searchEntity,
      };
      // Changing entity will refresh the angular page
      $scope.$watch('$ctrl.savedSearch.api_entity', function(newEntity, oldEntity) {
        if (newEntity && oldEntity && newEntity !== oldEntity) {
          $location.url('/create/' + newEntity);
        }
      });
    })

    // Controller for editing a SavedSearch
    .controller('searchEdit', function($scope, savedSearch) {
      searchEntity = savedSearch.api_entity;
      this.savedSearch = savedSearch;
      $scope.$ctrl = this;
    })

    .factory('searchMeta', function($q) {
      function getEntity(entityName) {
        if (entityName) {
          return _.find(CRM.crmSearchAdmin.schema, {name: entityName});
        }
      }
      // Get join metadata matching a given expression like "Email AS Contact_Email_contact_id_01"
      function getJoin(fullNameOrAlias) {
        var alias = _.last(fullNameOrAlias.split(' AS ')),
          path = alias,
          baseEntity = searchEntity,
          label = [],
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
          label.push(join.label + (num > 1 ? ' ' + num : ''));
          path = path.replace(/^\d\d_?/, '');
          if (path.length) {
            baseEntity = join.entity;
          }
        }
        result = _.assign(_.cloneDeep(join), {label: label.join(' - '), alias: alias, baseEntity: baseEntity});
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
          join = getJoin(dotSplit[0]);
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
        if (field) {
          field.baseEntity = entityName;
          return {field: field, join: join};
        }
      }
      function parseExpr(expr) {
        if (!expr) {
          return;
        }
        var splitAs = expr.split(' AS '),
          info = {fn: null, modifier: '', field: {}, alias: _.last(splitAs)},
          fieldName = splitAs[0],
          bracketPos = splitAs[0].indexOf('(');
        if (bracketPos >= 0) {
          var parsed = splitAs[0].substr(bracketPos).match(/[ ]?([A-Z]+[ ]+)?([\w.:]+)/);
          fieldName = parsed[2];
          info.fn = _.find(CRM.crmSearchAdmin.functions, {name: expr.substring(0, bracketPos)});
          info.modifier = _.trim(parsed[1]);
        }
        var fieldAndJoin = getFieldAndJoin(fieldName, searchEntity);
        if (fieldAndJoin) {
          var split = fieldName.split(':'),
            prefixPos = split[0].lastIndexOf(fieldAndJoin.field.name);
          info.path = split[0];
          info.prefix = prefixPos > 0 ? info.path.substring(0, prefixPos) : '';
          info.suffix = !split[1] ? '' : ':' + split[1];
          info.field = fieldAndJoin.field;
          info.join = fieldAndJoin.join;
        }
        return info;
      }
      return {
        getEntity: getEntity,
        getField: function(fieldName, entityName) {
          return getFieldAndJoin(fieldName, entityName).field;
        },
        getJoin: getJoin,
        parseExpr: parseExpr,
        getDefaultLabel: function(col) {
          var info = parseExpr(col),
            label = info.field.label;
          if (info.fn) {
            label = '(' + info.fn.title + ') ' + label;
          }
          if (info.join) {
            label = info.join.label + ': ' + label;
          }
          return label;
        },
        // Find all possible search columns that could serve as contact_id for a smart group
        getSmartGroupColumns: function(api_entity, api_params) {
          var joins = _.pluck((api_params.join || []), 0);
          return _.transform([api_entity].concat(joins), function(columns, joinExpr) {
            var joinName = joinExpr.split(' AS '),
              joinInfo = joinName[1] ? getJoin(joinName[1]) : {entity: joinName[0]},
              entity = getEntity(joinInfo.entity),
              prefix = joinInfo.alias ? joinInfo.alias + '.' : '';
            _.each(entity.fields, function(field) {
              if ((entity.name === 'Contact' && field.name === 'id') || (field.fk_entity === 'Contact' && joinInfo.baseEntity !== 'Contact')) {
                columns.push({
                  id: prefix + field.name,
                  text: (joinInfo.label ? joinInfo.label + ': ' : '') + field.label,
                  icon: entity.icon
                });
              }
            });
          });
        },
        pickIcon: function() {
          var deferred = $q.defer();
          $('#crm-search-admin-icon-picker').off('change').siblings('.crm-icon-picker-button').click();
          $('#crm-search-admin-icon-picker').on('change', function() {
            deferred.resolve($(this).val());
          });
          return deferred.promise;
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
    $('#crm-container').append('<div style="display:none"><input id="crm-search-admin-icon-picker"></div>');
    CRM.loadScript(CRM.config.resourceBase + 'js/jquery/jquery.crmIconPicker.js').done(function() {
      $('#crm-search-admin-icon-picker').crmIconPicker();
    });
  });

})(angular, CRM.$, CRM._);
