(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchInputVal', {
    bindings: {
      field: '<',
      'op': '<',
      'optionKey': '<',
      labelId: '@',
    },
    require: {ngModel: 'ngModel'},
    template: '<div class="form-group" ng-include="$ctrl.getTemplate()"></div>',
    controller: function($scope, formatForSelect2, crmApi4) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        var rendered = false,
          field = this.field || {};
        ctrl.dateRanges = CRM.crmSearchTasks.dateRanges;

        this.ngModel.$render = function() {
          ctrl.value = ctrl.ngModel.$viewValue;
          if (!rendered && isDateField(field)) {
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

      this.getFkEntity = function() {
        return ctrl.field ? ctrl.field.fk_entity || ctrl.field.entity : null;
      };

      var autocompleteStaticOptions = {
        Contact: ['user_contact_id'],
        '': []
      };

      this.getAutocompleteStaticOptions = function() {
        return autocompleteStaticOptions[ctrl.getFkEntity() || ''] || autocompleteStaticOptions[''];
      };

      this.isMulti = function() {
        // If there's a search operator, return `true` if the operator takes multiple values, else `false`
        if (ctrl.op) {
          return ctrl.op === 'IN' || ctrl.op === 'NOT IN';
        }
        // If no search operator this is an input for e.g. the bulk update action
        // Return `true` if the field is multi-valued, else `null`
        return ctrl.field && (ctrl.field.serialize || ctrl.field.data_type === 'Array') ? true : null;
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

      this.lookupAddress = function() {
        ctrl.value.geo_code_1 = null;
        ctrl.value.geo_code_2 = null;
        if (ctrl.value.address) {
          crmApi4('Address', 'getCoordinates', {
            address: ctrl.value.address
          }).then(function(coordinates) {
            if (coordinates[0]) {
              ctrl.value.geo_code_1 = coordinates[0].geo_code_1;
              ctrl.value.geo_code_2 = coordinates[0].geo_code_2;
            }
          });
        }
      };

      this.getTemplate = function() {
        var field = ctrl.field || {};

        if (_.includes(['LIKE', 'NOT LIKE', 'REGEXP', 'NOT REGEXP', 'REGEXP BINARY', 'NOT REGEXP BINARY'], ctrl.op)) {
          return '~/crmSearchTasks/crmSearchInput/text.html';
        }

        if (field.input_type === 'Location') {
          ctrl.value = ctrl.value || {distance_unit: CRM.crmSearchAdmin.defaultDistanceUnit};
          return '~/crmSearchTasks/crmSearchInput/location.html';
        }

        if (isDateField(field)) {
          return '~/crmSearchTasks/crmSearchInput/date.html';
        }

        if (field.data_type === 'Boolean') {
          return '~/crmSearchTasks/crmSearchInput/boolean.html';
        }

        if (!_.includes(['>', '<', '>=', '<='], ctrl.op)) {
          // Only use option list if the field has a "name" suffix
          if (field.options && (!field.suffixes || field.suffixes.includes('name'))) {
            return '~/crmSearchTasks/crmSearchInput/select.html';
          }
          if (field.fk_entity || field.name === 'id') {
            return '~/crmSearchTasks/crmSearchInput/entityRef.html';
          }
        }

        if (field.data_type === 'Integer') {
          return '~/crmSearchTasks/crmSearchInput/integer.html';
        }

        if (field.data_type === 'Float') {
          return '~/crmSearchTasks/crmSearchInput/float.html';
        }

        if (field.input_type === 'Email') {
          return '~/crmSearchTasks/crmSearchInput/email.html';
        }

        return '~/crmSearchTasks/crmSearchInput/text.html';
      };

      this.getFieldOptions = function() {
        var field = ctrl.field || {};
        return {results: formatForSelect2(field.options || [], ctrl.optionKey || 'id', 'label', ['description', 'color', 'icon'])};
      };

      function isDateField(field) {
        return field.data_type === 'Date' || field.data_type === 'Timestamp';
      }

    }
  });

})(angular, CRM.$, CRM._);
