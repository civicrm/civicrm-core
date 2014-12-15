(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailingAB2/' + relPath;
  };

  // example:
  //   scope.myAbtest = new CrmMailingAB();
  //   <crm-mailing-ab-block-mailing="{fromAddressA: 1, fromAddressB: 1}" crm-abtest="myAbtest" />
  angular.module('crmMailingAB2').directive('crmMailingAbBlockMailing', function ($parse) {
    return {
      scope: {
        crmMailingAbBlockMailing: '@',
        crmAbtest: '@'
      },
      templateUrl: partialUrl('joint-mailing.html'),
      link: function (scope, elm, attr) {
        var model = $parse(attr.crmAbtest);
        scope.abtest = model(scope.$parent);
        scope.crmMailingConst = CRM.crmMailing;
        scope.ts = CRM.ts('CiviMail');

        var fieldsModel = $parse(attr.crmMailingAbBlockMailing);
        scope.fields = fieldsModel(scope.$parent);
      }
    };
  });

  // example: <div crm-mailing-ab-slider ng-model="abtest.ab.group_percentage"></div>
  angular.module('crmMailingAB2').directive('crmMailingAbSlider', function () {
    return {
      require: '?ngModel',
      scope: {},
      templateUrl: partialUrl('slider.html'),
      link: function (scope, element, attrs, ngModel) {
        var TEST_MIN = 1, TEST_MAX = 50;
        var sliders = $('.slider-test,.slider-win', element);
        var sliderTests = $('.slider-test', element);
        var sliderWin = $('.slider-win', element);

        scope.ts = CRM.ts('CiviMail');
        scope.testValue = 0;
        scope.winValue = 100;

        // set the base value (following a GUI event)
        function setValue(value) {
          value = Math.min(TEST_MAX, Math.max(TEST_MIN, value));
          scope.$apply(function () {
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

        ngModel.$render = function () {
          scope.testValue = ngModel.$viewValue;
          scope.winValue = 100 - (2 * scope.testValue);
          sliderTests.slider('value', scope.testValue);
          sliderWin.slider('value', scope.winValue);
        };
      }
    };
  });
})(angular, CRM.$, CRM._);
