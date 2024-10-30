(function(angular, $, _) {

  // example: <div crm-mailing-ab-slider ng-model="abtest.ab.group_percentage"></div>
  angular.module('crmMailingAB').directive('crmMailingAbSlider', function() {
    return {
      require: '?ngModel',
      scope: {},
      templateUrl: '~/crmMailingAB/Slider.html',
      link: function(scope, element, attrs, ngModel) {
        var TEST_MIN = 1, TEST_MAX = 50;
        var sliders = $('.slider-test,.slider-win', element);
        var sliderTests = $('.slider-test', element);
        var sliderWin = $('.slider-win', element);

        scope.ts = CRM.ts('civi_mail');
        scope.testValue = 0;
        scope.winValue = 100;

        // set the base value (following a GUI event)
        function setValue(value) {
          value = Math.min(TEST_MAX, Math.max(TEST_MIN, value));
          scope.$apply(function() {
            ngModel.$setViewValue(value);
            scope.testValue = value;
            scope.winValue = 100 - (2 * scope.testValue);
            sliderTests.slider('value', scope.testValue);
            sliderWin.slider('value', scope.winValue);
          });
        }

        sliders.slider({
          min: 0,
          max: 100,
          range: 'min',
          step: 1
        });
        sliderTests.slider({
          slide: function slideTest(event, ui) {
            event.preventDefault();
            setValue(ui.value);
          }
        });
        sliderWin.slider({
          slide: function slideWinner(event, ui) {
            event.preventDefault();
            setValue(Math.round((100 - ui.value) / 2));
          }
        });

        ngModel.$render = function() {
          scope.testValue = ngModel.$viewValue;
          scope.winValue = 100 - (2 * scope.testValue);
          sliderTests.slider('value', scope.testValue);
          sliderWin.slider('value', scope.winValue);
        };
      }
    };
  });

})(angular, CRM.$, CRM._);
