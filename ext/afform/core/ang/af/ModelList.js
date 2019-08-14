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
        this.getData = function getData(name) {
          return data[name];
        };
        this.getSchema = function getSchema(name) {
          return schema[name];
        };
        this.loadData = function() {
          var apiCalls = {};
          _.each(schema, function(entity, entityName) {
            if ($routeParams[entityName]) {
              var id = $routeParams[entityName];
              apiCalls[entityName] = [entity.type, 'get', {select: entity.fields, where: [['id', '=', id]]}, 0];
            }
          });
          if (!_.isEmpty(apiCalls)) {
            crmApi4(apiCalls).then(function(resp) {
              data = resp;
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
