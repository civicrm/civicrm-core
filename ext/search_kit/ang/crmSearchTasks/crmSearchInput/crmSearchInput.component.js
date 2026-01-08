(function(angular, $, _) {
  "use strict";

  // Ensures each searchInput instance gets a unique id
  let searchInputInstance = 0;

  angular.module('crmSearchTasks').component('crmSearchInput', {
    bindings: {
      field: '<',
      op: '<',
      format: '<',
      optionKey: '<',
      showLabel: '<',
      name: '@',
    },
    require: {ngModel: 'ngModel'},
    templateUrl: '~/crmSearchTasks/crmSearchInput/crmSearchInput.html',
    controller: function($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.domId = 'search-input-' + searchInputInstance++;

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
          ctrl.value = formatDataType(ctrl.ngModel.$viewValue);
        };

        function formatDataType(val) {
          // Prevent unnecessarily triggering ngChagne
          if (val === null || val === undefined) {
            return val;
          }
          // Do not reformat pseudoconstant values (:name, :label, etc)
          if (ctrl.optionKey && ctrl.optionKey !== 'id') {
            return val;
          }
          // A regex is always a string
          if (ctrl.op && ctrl.op.includes('REGEXP')) {
            return val;
          }
          if (Array.isArray(val)) {
            const formatted = angular.copy(val);
            formatted.forEach((v, i) => formatted[i] = formatDataType(v));
            return formatted;
          }
          // Format numbers but skip partial date functions which get special handling
          if (['Integer', 'Float'].includes(ctrl.field ? ctrl.field.data_type : null) && ctrl.field.category !== 'partial_date') {
            let newVal = Number(val);
            // FK Entities can use a mix of numeric & string values (see "static" options)
            // Also see afGuiFieldValue.convertDataType
            if ((ctrl.field.name === 'id' || ctrl.field.fk_entity) && ('' + newVal) !== val) {
              return val;
            }
            return newVal;
          }
          return val;
        }

      };
    }
  });

})(angular, CRM.$, CRM._);
