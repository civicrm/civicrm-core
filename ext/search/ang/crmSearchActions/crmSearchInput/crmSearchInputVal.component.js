(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchActions').component('crmSearchInputVal', {
    bindings: {
      field: '<',
      'multi': '<',
      'optionKey': '<'
    },
    require: {ngModel: 'ngModel'},
    template: '<div class="form-group" ng-include="$ctrl.getTemplate()"></div>',
    controller: function($scope, formatForSelect2) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.$onInit = function() {
        var rendered = false;
        ctrl.dateRanges = CRM.crmSearchActions.dateRanges;

        this.ngModel.$render = function() {
          ctrl.value = ctrl.ngModel.$viewValue;
          if (!rendered && ctrl.field.input_type === 'Date') {
            setDateType();
          }
          rendered = true;
        };

        $scope.$watch('$ctrl.value', function() {
          ctrl.ngModel.$setViewValue(ctrl.value);
        });

        function setDateType() {
          if (_.findWhere(ctrl.dateRanges, {id: ctrl.value})) {
            ctrl.dateType = 'range';
          } else if (ctrl.value === 'now') {
            ctrl.dateType = 'now';
          } else if (_.includes(ctrl.value, 'now -')) {
            ctrl.dateType = 'now -';
          } else if (_.includes(ctrl.value, 'now +')) {
            ctrl.dateType = 'now +';
          } else {
            ctrl.dateType = 'fixed';
          }
        }
      };

      this.changeDateType = function() {
        switch (ctrl.dateType) {
          case 'fixed':
            ctrl.value = '';
            break;

          case 'range':
            ctrl.value = ctrl.dateRanges[0].id;
            break;

          case 'now':
            ctrl.value = 'now';
            break;

          default:
            ctrl.value = ctrl.dateType + ' 1 day';
        }
      };

      this.dateUnits = function(setUnit) {
        var vals = ctrl.value.split(' ');
        if (arguments.length) {
          vals[3] = setUnit;
          ctrl.value = vals.join(' ');
        } else {
          return vals[3];
        }
      };

      this.dateNumber = function(setNumber) {
        var vals = ctrl.value.split(' ');
        if (arguments.length) {
          vals[2] = setNumber;
          ctrl.value = vals.join(' ');
        } else {
          return parseInt(vals[2], 10);
        }
      };

      this.getTemplate = function() {

        if (ctrl.field.input_type === 'Date') {
          return '~/crmSearchActions/crmSearchInput/date.html';
        }

        if (ctrl.field.data_type === 'Boolean') {
          return '~/crmSearchActions/crmSearchInput/boolean.html';
        }

        if (ctrl.field.options) {
          return '~/crmSearchActions/crmSearchInput/select.html';
        }

        if (ctrl.field.fk_entity) {
          return '~/crmSearchActions/crmSearchInput/entityRef.html';
        }

        if (ctrl.field.data_type === 'Integer') {
          return '~/crmSearchActions/crmSearchInput/integer.html';
        }

        return '~/crmSearchActions/crmSearchInput/text.html';
      };

      this.getFieldOptions = function() {
        return {results: formatForSelect2(ctrl.field.options, ctrl.optionKey || 'id', 'label', ['description', 'color', 'icon'])};
      };

    }
  });

})(angular, CRM.$, CRM._);
