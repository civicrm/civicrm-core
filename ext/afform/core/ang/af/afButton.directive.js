(function(angular, $, _) {
  "use strict";
  angular.module('af').directive('afButton', function() {
    return {
      restrict: 'C',
      bindToController: {
        type: '@'
      },
      require: {
        afForm: '?^^afForm',
      },
      controller: function($scope, $element) {
        const ctrl = this;

        this.$onInit = function () {
          const type = $element.attr('type');

          // Pass off handling of reset button to appropriate controllers
          if (type === 'reset') {
            $element.on('click', function(e) {
              e.preventDefault();
              $scope.$apply(function() {
                // Reset submission form
                if (ctrl.afForm) {
                  ctrl.afForm.resetForm();
                }
                // Reset search form
                else {
                  $scope.$parent.$broadcast('afFormReset');
                }
              });
            });
          }
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
