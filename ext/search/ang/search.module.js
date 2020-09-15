(function(angular, $, _) {
  "use strict";

  // Shared between router and searchMeta service
  var searchEntity,
    // For loading saved search
    savedSearch,
    undefined;

  // Declare module and route/controller/services
  angular.module('search', CRM.angRequires('search'))

    .config(function($routeProvider) {
      $routeProvider.when('/:mode/:entity/:name?', {
        controller: 'searchRoute',
        template: '<div id="bootstrap-theme" class="crm-search"><crm-search ng-if="$ctrl.mode === \'create\'" entity="$ctrl.entity" load=":: $ctrl.savedSearch"></crm-search></div>',
        reloadOnSearch: false,
        resolve: {
          // For paths like /load/Group/MySmartGroup, load the group, stash it in the savedSearch variable, and then redirect
          // For paths like /create/Contact, return the stashed savedSearch if present
          savedSearch: function($route, $location, $timeout, crmApi4) {
            var retrievedSearch = savedSearch,
              params = $route.current.params;
            savedSearch = undefined;
            switch (params.mode) {
              case 'create':
                return retrievedSearch;

              case 'load':
                // In theory savedSearches could be attached to something other than groups, but for now that's not supported
                if (params.entity !== 'Group' || !params.name) {
                  throw 'Failed to load ' + params.entity;
                }
                return crmApi4(params.entity, 'get', {
                  select: ['id', 'title', 'saved_search_id', 'saved_search.api_entity', 'saved_search.api_params'],
                  where: [['name', '=', params.name]]
                }, 0).then(function(retrieved) {
                  savedSearch = {
                    type: params.entity,
                    id: retrieved.id,
                    title: retrieved.title,
                    saved_search_id: retrieved.saved_search_id,
                    api_params: retrieved['saved_search.api_params']
                  };
                  $timeout(function() {
                    $location.url('/create/' + retrieved['saved_search.api_entity']);
                  });
                });
            }
          }
        }
      });
    })

    // Controller binds entity to route
    .controller('searchRoute', function($scope, $routeParams, $location, savedSearch) {
      searchEntity = this.entity = $routeParams.entity;
      this.mode = $routeParams.mode;
      this.savedSearch = savedSearch;
      $scope.$ctrl = this;

      // Changing entity will refresh the angular page
      $scope.$watch('$ctrl.entity', function(newEntity, oldEntity) {
        if (newEntity && oldEntity && newEntity !== oldEntity) {
          $location.url('/create/' + newEntity);
        }
      });
    })

    .factory('searchMeta', function() {
      function getEntity(entityName) {
        if (entityName) {
          entityName = entityName === true ? searchEntity : entityName;
          return _.find(CRM.vars.search.schema, {name: entityName});
        }
      }
      function getField(name) {
        var dotSplit = name.split('.'),
          joinEntity = dotSplit.length > 1 ? dotSplit[0] : null,
          fieldName = _.last(dotSplit).split(':')[0],
          entityName = searchEntity;
        // Custom fields contain a dot in their fieldname
        // If 3 segments, the first is the joinEntity and the last 2 are the custom field
        if (dotSplit.length === 3) {
          fieldName = dotSplit[1] + '.' + fieldName;
        }
        // If 2 segments, it's ambiguous whether this is a custom field or joined field. Search the main entity first.
        if (dotSplit.length === 2) {
          var field = _.find(getEntity(true).fields, {name: dotSplit[0] + '.' + fieldName});
          if (field) {
            return field;
          }
        }
        if (joinEntity) {
          entityName = _.find(CRM.vars.search.links[entityName], {alias: joinEntity}).entity;
        }
        return _.find(getEntity(entityName).fields, {name: fieldName});
      }
      return {
        getEntity: getEntity,
        getField: getField,
        parseExpr: function(expr) {
          var result = {},
            fieldName = expr,
            bracketPos = expr.indexOf('(');
          if (bracketPos >= 0) {
            fieldName = expr.match(/[A-Z( _]*([\w.:]+)/)[1];
            result.fn = _.find(CRM.vars.search.functions, {name: expr.substring(0, bracketPos)});
          }
          result.field = getField(fieldName);
          var split = fieldName.split(':'),
            prefixPos = split[0].lastIndexOf(result.field.name);
          result.path = split[0];
          result.prefix = prefixPos > 0 ? result.path.substring(0, prefixPos) : '';
          result.suffix = !split[1] ? '' : ':' + split[1];
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
