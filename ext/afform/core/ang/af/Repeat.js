(function(angular, $, _) {
  // Example usage: <div af-repeat="Email" min="1" max="3" add-label="Add email" ><div repeat-email-default /></div>
  angular.module('af')
    .directive('afRepeat', function() {
      return {
        restrict: 'A',
        require: ['?afFieldset', '?afJoin'],
        transclude: true,
        scope: {
          min: '=',
          max: '=',
          addLabel: '@afRepeat',
          addIcon: '@'
        },
        templateUrl: '~/af/afRepeat.html',
        link: function($scope, $el, $attr, ctrls) {
          $scope.afFieldset = ctrls[0];
          $scope.afJoin = ctrls[1];
        },
        controller: function($scope) {
          this.getItems = $scope.getItems = function() {
            var data = getEntityController().getData();
            while ($scope.min && data.length < $scope.min) {
              data.push(getRepeatType() === 'join' ? {} : {fields: {}, joins: {}});
            }
            return data;
          };

          function getRepeatType() {
            return $scope.afJoin ? 'join' : 'fieldset';
          }
          this.getRepeatType = getRepeatType;

          function getEntityController() {
            return $scope.afJoin || $scope.afFieldset;
          }
          this.getEntityController = getEntityController;

          $scope.addItem = function() {
            $scope.getItems().push(getRepeatType() === 'join' ? {} : {fields: {}});
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
    .directive('afRepeatItem', function() {
      return {
        restrict: 'A',
        require: ['afRepeatItem', '^^afRepeat'],
        bindToController: {
          item: '=afRepeatItem',
          repeatIndex: '='
        },
        link: function($scope, $el, $attr, ctrls) {
          var self = ctrls[0];
          self.afRepeat = ctrls[1];
        },
        controller: function($scope) {
          this.getFieldData = function() {
            return this.afRepeat.getRepeatType() === 'join' ? this.item : this.item.fields;
          };

          this.getEntityType = function() {
            return this.afRepeat.getEntityController().getEntityType();
          };
        }
      };
    });
})(angular, CRM.$, CRM._);
