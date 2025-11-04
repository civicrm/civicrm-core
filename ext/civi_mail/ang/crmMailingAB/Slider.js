(function(angular, $, _) {

  // example: <div crm-mailing-ab-slider ng-model="abtest.ab.group_percentage"></div>
  angular.module('crmMailingAB').directive('crmMailingAbSlider', function() {
    return {
      scope: {
        testValue: '=ngModel',
      },
      templateUrl: '~/crmMailingAB/Slider.html',
      link: function(scope, element, attrs) {
        const TEST_MIN = 1, TEST_MAX = 50;
        scope.ts = CRM.ts('civi_mail');

        scope.testValue = Number(scope.testValue) || 10;
        scope.winValue = 100 - (2 * scope.testValue);

        // set the base value (following a GUI event)
        scope.onChangeWinValue = () => {
          scope.testValue = (100 - scope.winValue) / 2;
        };

        // Watch external changes as well as user interaction with the test sliders
        scope.$watch('testValue', (newValue, oldValue) => {
          if (newValue !== oldValue) {
            if (newValue > TEST_MAX) {
              scope.testValue = TEST_MAX;
            }
            if (newValue < TEST_MIN) {
              scope.testValue = TEST_MIN;
            }
            scope.winValue = 100 - (2 * scope.testValue);
          }
        });
      }
    };
  });

})(angular, CRM.$, CRM._);
