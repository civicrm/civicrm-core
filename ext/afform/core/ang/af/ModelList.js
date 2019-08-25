(function(angular, $, _) {
  // "afModelList" is a basic skeletal directive.
  // Example usage: <af-model-list ctrl="myCtrl">
  angular.module('af').directive('afModelList', function() {
    return {
      restrict: 'AE',
      scope: {
        // afModelList: '=',
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
          schema[entity.name] = entity;
          data[entity.name] = data[entity.name] || {};
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
                  data[item.name] = item.values;
                });
              });
          }
        };

        this.submit = function submit() {
          CRM.alert('TODO: Submit');
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
