(function(angular, $, _) {
  // "afForm" is a basic skeletal directive.
  // Example usage: <af-form ctrl="myCtrl">
  angular.module('af').directive('afForm', function() {
    return {
      restrict: 'AE',
      scope: {
        // afForm: '=',
        ctrl: '@'
      },
      link: {
        post: function($scope, $el, $attr) {
          $scope.myCtrl.loadData();
        }
      },
      controller: function($scope, $routeParams, crmApi4) {
        var schema = {}, data = {};

        $scope.$parent[$scope.ctrl] = this;
        // Maybe there's a better way to export this controller to scope?
        $scope.myCtrl = this;

        this.registerEntity = function registerEntity(entity) {
          schema[entity.modelName] = entity;
          data[entity.modelName] = entity.data || {};
        };
        this.registerField = function(entityName, fieldName) {
          schema[entityName].fields.push(fieldName);
        };
        this.getEntity = function getEntity(name) {
          return schema[name];
        };
        // Returns field values for a given entity
        this.getData = function getData(name) {
          return data[name];
        };
        this.getSchema = function getSchema(name) {
          return schema[name];
        };
        this.loadData = function() {
          var toLoad = 0;
          _.each(schema, function(entity, entityName) {
            if ($routeParams[entityName] || entity.autofill) {
              toLoad++;
            }
          });
          if (toLoad) {
            crmApi4('Afform', 'prefill', {name: CRM.afform.open, args: $routeParams})
              .then(function(result) {
                _.each(result, function(item) {
                  data[item.name] = _.extend(item.values, schema[item.name].data || {});
                });
              });
          }
        };

        this.submit = function submit() {
          crmApi4('Afform', 'submit', {name: CRM.afform.open, args: $routeParams, values: data});
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
