(function(angular, $, _) {
  "use strict";

  // Shared between router and searchMeta service
  var searchEntity;

  // Declare module and route/controller/services
  angular.module('search', CRM.angRequires('search'))

    .config(function($routeProvider) {
      $routeProvider.when('/:entity', {
        controller: 'searchRoute',
        template: '<div id="bootstrap-theme" class="crm-search"><crm-search entity="entity"></crm-search></div>',
        reloadOnSearch: false
      });
    })

    // Controller binds entity to route
    .controller('searchRoute', function($scope, $routeParams, $location) {
      searchEntity = $scope.entity = $routeParams.entity;

      // Changing entity will refresh the angular page
      $scope.$watch('entity', function(newEntity, oldEntity) {
        if (newEntity && oldEntity && newEntity !== oldEntity) {
          $location.url('/' + newEntity);
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
