(function(angular, $, _) {
  "use strict";

  // Shared between router and searchMeta service
  var searchEntity,
    undefined;

  // Declare module and route/controller/services
  angular.module('searchAdmin', CRM.angRequires('searchAdmin'))

    .config(function($routeProvider) {
      $routeProvider.when('/list', {
        controller: 'searchList',
        templateUrl: '~/searchAdmin/searchList.html',
        resolve: {
          // Load data for lists
          savedSearches: function(crmApi4) {
            return crmApi4('SavedSearch', 'get', {
              select: ['id', 'api_entity', 'form_values', 'COUNT(search_display.id) AS displays', 'GROUP_CONCAT(group.title) AS groups'],
              join: [['SearchDisplay AS search_display'], ['Group AS group']],
              where: [['api_entity', 'IS NOT NULL']],
              groupBy: ['id']
            });
          }
        }
      });
      $routeProvider.when('/create/:entity', {
        controller: 'searchCreate',
        template: '<crm-search saved-search="$ctrl.savedSearch"></crm-search>',
      });
      $routeProvider.when('/edit/:id', {
        controller: 'searchEdit',
        template: '<crm-search saved-search="$ctrl.savedSearch"></crm-search>',
        resolve: {
          // Load saved search
          savedSearch: function($route, crmApi4) {
            var params = $route.current.params;
            return crmApi4('SavedSearch', 'get', {
              where: [['id', '=', params.id]],
              chain: {
                group: ['Group', 'get', {where: [['saved_search_id', '=', '$id']]}, 0],
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
          name = _.last(dotSplit).split(':')[0];
        // Custom fields contain a dot in their fieldname
        // If 3 segments, the first is the joinEntity and the last 2 are the custom field
        if (dotSplit.length === 3) {
          name = dotSplit[1] + '.' + name;
        }
        // If 2 segments, it's ambiguous whether this is a custom field or joined field. Search the main entity first.
        if (dotSplit.length === 2) {
          var field = _.find(getEntity(entityName).fields, {name: dotSplit[0] + '.' + name});
          if (field) {
            return field;
          }
        }
        if (joinEntity) {
          entityName = _.find(CRM.vars.search.links[entityName], {alias: joinEntity}).entity;
        }
        return _.find(getEntity(entityName).fields, {name: name});
      }
      return {
        getEntity: getEntity,
        getField: getField,
        parseExpr: function(expr) {
          var result = {fn: null, modifier: ''},
            fieldName = expr,
            bracketPos = expr.indexOf('(');
          if (bracketPos >= 0) {
            var parsed = expr.substr(bracketPos).match(/[ ]?([A-Z]+[ ]+)?([\w.:]+)/);
            fieldName = parsed[2];
            result.fn = _.find(CRM.searchAdmin.functions, {name: expr.substring(0, bracketPos)});
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
      };
    })

    // Reformat an array of objects for compatibility with select2
    // Todo this probably belongs in core
    .factory('formatForSelect2', function() {
      return function(input, key, label, extra) {
        return _.transform(input, function(result, item) {
          var formatted = {id: item[key], text: item[label]};
          if (extra) {
            _.merge(formatted, _.pick(item, extra));
          }
          result.push(formatted);
        }, []);
      };
    });

})(angular, CRM.$, CRM._);
