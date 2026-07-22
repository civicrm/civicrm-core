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
          afRepeatDefault: '=',
          addLabel: '@afRepeat',
          addIcon: '@',
          copyLabel: '@afCopy',
          copyIcon: '@'
        },
        templateUrl: '~/af/afRepeat.html',
        link: function($scope, $el, $attr, ctrls) {
          $scope.afFieldset = ctrls[0];
          $scope.afJoin = ctrls[1];
          $scope.element = $el;
        },
        controller: function($scope) {

          this.$onInit = () => {
            $scope.$evalAsync(() => {
              const data = getEntityController().getData();
              let defaultCount = $scope.afRepeatDefault;
              if (defaultCount === undefined || defaultCount === '' || isNaN(defaultCount)) {
                defaultCount = 1;
              }
              if ($scope.min !== undefined && $scope.min !== '' && defaultCount < $scope.min) {
                defaultCount = $scope.min;
              }
              while (data.length < defaultCount) {
                getEntityController().addRepeatItem();
              }
            });
          };

          this.getItems = $scope.getItems = () => {
            return getEntityController().getData();
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
            getEntityController().addRepeatItem();
          };

          $scope.copyItem = function() {
            const data = $scope.getItems();
            const last = data[data.length - 1];
            data.push(getRepeatType() === 'join' ? angular.copy(last) : {fields: angular.copy(last.fields), extras: {}});
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
    // @internal directive used within the afRepeat directive, invoked once per iteration
    .directive('afRepeatItem', function() {
      return {
        restrict: 'A',
        require: {
          afRepeat: '^^',
          outerRepeatItem: '?^^afRepeatItem'
        },
        bindToController: {
          item: '=afRepeatItem',
          repeatIndex: '='
        },
        controller: function() {
          this.getFieldData = function() {
            return this.afRepeat.getRepeatType() === 'join' ? this.item : this.item.fields;
          };

          // Per-slot "extra" field storage for a repeating fieldset.
          // Joins have no per-slot extras, so return null there and let
          // afField fall back to the form-level extras object.
          this.getExtrasData = function() {
            if (this.afRepeat.getRepeatType() === 'join') {
              return null;
            }
            this.item.extras = this.item.extras || {};
            return this.item.extras;
          };

          this.getEntityType = function() {
            return this.afRepeat.getEntityController().getEntityType();
          };
        }
      };
    });
})(angular, CRM.$, CRM._);
