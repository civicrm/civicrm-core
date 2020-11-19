(function(angular, $, _) {
  "use strict";

  // Shared between router and searchMeta service
  var searchEntity,
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
                groups: ['Group', 'get', {select: ['id', 'title', 'description', 'visibility', 'group_type'], where: [['saved_search_id', '=', '$id']]}],
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

    .factory('searchMeta', function() {
      function getEntity(entityName) {
        if (entityName) {
          return _.find(CRM.vars.search.schema, {name: entityName});
        }
      }
      function getField(fieldName, entityName) {
        var dotSplit = fieldName.split('.'),
          joinEntity = dotSplit.length > 1 ? dotSplit[0] : null,
          name = _.last(dotSplit).split(':')[0],
          field;
        // Custom fields contain a dot in their fieldname
        // If 3 segments, the first is the joinEntity and the last 2 are the custom field
        if (dotSplit.length === 3) {
          name = dotSplit[1] + '.' + name;
        }
        // If 2 segments, it's ambiguous whether this is a custom field or joined field. Search the main entity first.
        if (dotSplit.length === 2) {
          field = _.find(getEntity(entityName).fields, {name: dotSplit[0] + '.' + name});
          if (field) {
            field.entity = entityName;
            return field;
          }
        }
        if (joinEntity) {
          entityName = _.find(CRM.vars.search.links[entityName], {alias: joinEntity}).entity;
        }
        field = _.find(getEntity(entityName).fields, {name: name});
        if (field) {
          field.entity = entityName;
          return field;
        }
      }
      function parseExpr(expr) {
        var result = {fn: null, modifier: ''},
          fieldName = expr,
          bracketPos = expr.indexOf('(');
        if (bracketPos >= 0) {
          var parsed = expr.substr(bracketPos).match(/[ ]?([A-Z]+[ ]+)?([\w.:]+)/);
          fieldName = parsed[2];
          result.fn = _.find(CRM.crmSearchAdmin.functions, {name: expr.substring(0, bracketPos)});
          result.modifier = _.trim(parsed[1]);
        }
        result.field = expr ? getField(fieldName, searchEntity) : undefined;
        if (result.field) {
          var split = fieldName.split(':'),
            prefixPos = split[0].lastIndexOf(result.field.name);
          result.path = split[0];
          result.prefix = prefixPos > 0 ? result.path.substring(0, prefixPos) : '';
          result.suffix = !split[1] ? '' : ':' + split[1];
        }
        return result;
      }
      return {
        getEntity: getEntity,
        getField: getField,
        parseExpr: parseExpr,
        getDefaultLabel: function(col) {
          var info = parseExpr(col),
            label = info.field.label;
          if (info.fn) {
            label = '(' + info.fn.title + ') ' + label;
          }
          return label;
        },
        // Find all possible search columns that could serve as contact_id for a smart group
        getSmartGroupColumns: function(api_entity, api_params) {
          var joins = _.pluck((api_params.join || []), 0),
            entityCount = {};
          return _.transform([api_entity].concat(joins), function(columns, joinExpr) {
            var joinName = joinExpr.split(' AS '),
              entityName = joinName[0],
              entity = getEntity(entityName),
              prefix = joinName[1] ? joinName[1] + '.' : '';
            _.each(entity.fields, function(field) {
              if ((entityName === 'Contact' && field.name === 'id') || field.fk_entity === 'Contact') {
                columns.push({
                  id: prefix + field.name,
                  text: entity.title_plural + (entityCount[entityName] ? ' ' + entityCount[entityName] : '') + ': ' + field.label,
                  icon: entity.icon
                });
              }
            });
            entityCount[entityName] = 1 + (entityCount[entityName] || 1);
          });
        }
      };
    });

})(angular, CRM.$, CRM._);
