(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchInputVal', {
    bindings: {
      field: '<',
      'multi': '<',
      'optionKey': '<'
    },
    require: {ngModel: 'ngModel'},
    template: '<div class="form-group" ng-include="$ctrl.getTemplate()"></div>',
    controller: function($scope, formatForSelect2) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        var rendered = false;
        ctrl.dateRanges = CRM.crmSearchTasks.dateRanges;
        ctrl.entity = ctrl.field.fk_entity || ctrl.field.entity;

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
        var field = ctrl.field || {};

        if (field.input_type === 'Date') {
          return '~/crmSearchTasks/crmSearchInput/date.html';
        }

        if (field.data_type === 'Boolean') {
          return '~/crmSearchTasks/crmSearchInput/boolean.html';
        }

        if (field.options) {
          return '~/crmSearchTasks/crmSearchInput/select.html';
        }

        if (field.fk_entity || field.name === 'id') {
          return '~/crmSearchTasks/crmSearchInput/entityRef.html';
        }

        if (field.data_type === 'Integer') {
          return '~/crmSearchTasks/crmSearchInput/integer.html';
        }

        if (field.data_type === 'Float') {
          return '~/crmSearchTasks/crmSearchInput/float.html';
        }

        return '~/crmSearchTasks/crmSearchInput/text.html';
      };

      this.getFieldOptions = function() {
        var field = ctrl.field || {};
        return {results: formatForSelect2(field.options || [], ctrl.optionKey || 'id', 'label', ['description', 'color', 'icon'])};
      };

    }
  });

})(angular, CRM.$, CRM._);
