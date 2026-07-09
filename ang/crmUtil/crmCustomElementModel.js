// https://civicrm.org/licensing
(function (angular, $, _) {
  "use strict";

  /**
   * Allows using ng-model with a custom element like civi-rich-text-input
   */
  angular.module("crmUtil").directive("crmCustomElementModel", () => ({
    restrict: "A",
    require: 'ngModel',
    link: function ($scope, $element, attributes, ngModelController) {
      // get the native element
      const element = $element[0];
      // update element value from angular in render hook
      ngModelController.$render = () => element.value = ngModelController.$viewValue;
      // update angular value from element when element is changed
      element.addEventListener('change', () => ngModelController.$setViewValue(element.value));
    }
  }));

})(angular, CRM.$, CRM._);
