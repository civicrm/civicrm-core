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
        var entities = {};

        $scope.$parent[$scope.ctrl] = this;

        this.registerEntity = function registerEntity(entity) {
          // console.log('register', entity.name);
          entities[entity.name] = entity;
        };
        this.getEntity = function getEntity(name) {
          // console.log('get', name);
          return entities[name];
        };

        // TODO: Support for tapping into load+save API calls

        this.submit = function submit() {
          CRM.alert('TODO: Submit');
        };
      }]
    };
  });
})(angular, CRM.$, CRM._);
