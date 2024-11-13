(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchInput', {
    bindings: {
      field: '<',
      op: '<',
      format: '<',
      optionKey: '<',
      showLabel: '<',
    },
    require: {ngModel: 'ngModel'},
    templateUrl: '~/crmSearchTasks/crmSearchInput/crmSearchInput.html',
    controller: function($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.domId = 'search-input-' + Math.random().toString(36).substr(2, 9);

      this.$onInit = function() {

        $scope.$watch('$ctrl.value', function() {
          ctrl.ngModel.$setViewValue(ctrl.value);
        });

        // For the ON clause, string values must be quoted
        ctrl.ngModel.$parsers.push(function(viewValue) {
          return ctrl.format === 'json' && _.isString(viewValue) && viewValue.length ? JSON.stringify(viewValue) : viewValue;
        });

        // For the ON clause, unquote string values
        ctrl.ngModel.$formatters.push(function(value) {
          return ctrl.format === 'json' && _.isString(value) && value.length ? JSON.parse(value) : value;
        });

        this.ngModel.$render = function() {
          ctrl.value = ctrl.ngModel.$viewValue;
        };

      };
    }
  });

})(angular, CRM.$, CRM._);
