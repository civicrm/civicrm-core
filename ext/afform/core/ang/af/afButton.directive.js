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

        this.$onInit = () => {
          const type = $element.attr('type');

          // Pass off handling of reset button to appropriate controllers
          if (type === 'reset') {
            $element.on('click', (e) => {
              e.preventDefault();
              $scope.$apply(() => {
                // Reset submission form
                if (this.afForm) {
                  this.afForm.resetForm();
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
