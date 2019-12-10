(function(angular, $, _) {
  // Example usage: <div af-block="Email" min="1" max="3" add-label="Add email" ><div block-email-default /></div>
  angular.module('af')
    .directive('afBlock', function() {
      return {
        restrict: 'A',
        require: ['^^afFieldset'],
        scope: {
          blockName: '@afBlock',
          min: '=',
          max: '=',
          addLabel: '@',
          addIcon: '@'
        },
        transclude: true,
        templateUrl: '~/af/afBlock.html',
        link: function($scope, $el, $attr, ctrls) {
          var ts = $scope.ts = CRM.ts('afform');
          $scope.afFieldset = ctrls[0];
        },
        controller: function($scope) {
          this.getItems = $scope.getItems = function() {
            var data = $scope.afFieldset.getData();
            data.blocks = data.blocks || {};
            var block = (data.blocks[$scope.blockName] = data.blocks[$scope.blockName] || []);
            while ($scope.min && block.length < $scope.min) {
              block.push({});
            }
            return block;
          };

          $scope.addItem = function() {
            $scope.getItems().push({});
          };

          $scope.removeItem = function(index) {
            $scope.getItems().splice(index, 1);
          };

          $scope.canAdd = function() {
            return !$scope.max || $scope.getItems().length < $scope.max;
          };

          $scope.canRemove = function() {
            return !$scope.min || $scope.getItems().length > $scope.min;
          };
        }
      };
    })
    .directive('afBlockItem', function() {
      return {
        restrict: 'A',
        scope: {
          item: '=afBlockItem'
        },
        controller: function($scope) {
          this.getData = function() {
            return $scope.item;
          };
        }
      };
    });
})(angular, CRM.$, CRM._);
