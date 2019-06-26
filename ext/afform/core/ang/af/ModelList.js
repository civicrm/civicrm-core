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
      link: function($scope, $el, $attr) {},
      controller: ['$scope', function($scope) {
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

        this.submit = function submit() {
          CRM.alert('TODO: Submit');
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
