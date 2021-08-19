(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchInput', {
    bindings: {
      field: '<',
      'op': '<',
      'format': '<',
      'optionKey': '<'
    },
    require: {ngModel: 'ngModel'},
    templateUrl: '~/crmSearchTasks/crmSearchInput/crmSearchInput.html',
    controller: function($scope) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.isMulti = function() {
        // If there's a search operator, return `true` if the operator takes multiple values, else `false`
        if (ctrl.op) {
          return ctrl.op === 'IN' || ctrl.op === 'NOT IN';
        }
        // If no search operator this is an input for e.g. the bulk update action
        // Return `true` if the field is multi-valued, else `null`
        return ctrl.field && (ctrl.field.serialize || ctrl.field.data_type === 'Array') ? true : null;
      };

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
