(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchInputVal', {
    bindings: {
      op: '<',
      labelId: '@',
    },
    require: {
      ngModel: 'ngModel',
      input: '^crmSearchInput'
    },
    template: '<div class="form-group" ng-include="$ctrl.getTemplate()"></div>',
    controller: function($scope, formatForSelect2, crmApi4) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        // Dynamic "last N units including today" relative date, e.g. "ending_45.day"
        // @see CRM_Utils_Date::DYNAMIC_RELATIVE_DATE_PATTERN
        endingPattern = /^ending_(\d+)\.(day|week|month|quarter|year)$/;

      this.$onInit = function() {
        const field = getField();
        let rendered = false;
        this.dateRanges = CRM.crmSearchTasks.dateRanges;

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
          if (ctrl.dateRanges.find((dateRange) => dateRange.id === ctrl.value)) {
            ctrl.dateType = 'range';
          } else if (endingPattern.test(ctrl.value)) {
            ctrl.dateType = 'ending';
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
        const field = getField();
        return field.fk_entity || field.entity || null;
      };

      const autocompleteStaticOptions = {
        Contact: ['user_contact_id'],
        Individual: ['user_contact_id'],
        '': []
      };

      this.getAutocompleteStaticOptions = function() {
        // "Select current user" only make sense in a search context, so check for presence of operator
        if (ctrl.op) {
          return autocompleteStaticOptions[ctrl.getFkEntity() || ''] || autocompleteStaticOptions[''];
        }
        return autocompleteStaticOptions[''];
      };

      this.isMulti = function() {
        // If there's a search operator, return `true` if the operator takes multiple values, else `false`
        if (ctrl.op) {
          return ctrl.op === 'IN' || ctrl.op === 'NOT IN' || ctrl.op === 'CONTAINS' || ctrl.op === 'NOT CONTAINS' || ctrl.op === 'CONTAINS ONE OF' || ctrl.op === 'NOT CONTAINS ONE OF';
        }
        // If no search operator this is an input for e.g. the bulk update action
        // Return `true` if the field is multi-valued, else `null`
        return ctrl.input.field && (ctrl.input.field.serialize || ctrl.input.field.data_type === 'Array') ? true : null;
      };

      this.changeDateType = function() {
        switch (ctrl.dateType) {
          case 'fixed':
            ctrl.value = '';
            break;

          case 'range':
            ctrl.value = ctrl.dateRanges[0].id;
            break;

          case 'ending':
            ctrl.value = 'ending_14.day';
            break;

          case 'now':
            ctrl.value = 'now';
            break;

          default:
            ctrl.value = ctrl.dateType + ' 1 day';
        }
      };

      // Getter/setters for a dynamic "ending_N.unit" relative date
      const getEnding = () => endingPattern.exec(ctrl.value) || [null, '14', 'day'];
      this.endingNumber = function(setNumber) {
        if (arguments.length) {
          ctrl.value = 'ending_' + Math.max(1, parseInt(setNumber, 10) || 1) + '.' + getEnding()[2];
        }
        return parseInt(getEnding()[1], 10);
      };
      this.endingUnits = function(setUnit) {
        if (arguments.length) {
          ctrl.value = 'ending_' + getEnding()[1] + '.' + setUnit;
        }
        return getEnding()[2];
      };

      this.dateUnits = function(setUnit) {
        const vals = ctrl.value.split(' ');
        if (arguments.length) {
          vals[3] = setUnit;
          ctrl.value = vals.join(' ');
        } else {
          return vals[3];
        }
      };

      this.dateNumber = function(setNumber) {
        const vals = ctrl.value.split(' ');
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
        const field = getField();

        if (['LIKE', 'NOT LIKE', 'REGEXP', 'NOT REGEXP', 'REGEXP BINARY', 'NOT REGEXP BINARY'].includes(ctrl.op)) {
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

        if (field.input_type === 'Color') {
          return '~/crmSearchTasks/crmSearchInput/color.html';
        }

        if (!['>', '<', '>=', '<='].includes(ctrl.op)) {
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

        if (field.input_type === 'TextArea') {
          return '~/crmSearchTasks/crmSearchInput/textArea.html';
        }

        return '~/crmSearchTasks/crmSearchInput/text.html';
      };

      this.getFieldOptions = function() {
        const field = getField();
        return {results: formatForSelect2(field.options || [], ctrl.input.optionKey || 'id', 'label', ['description', 'color', 'icon'])};
      };

      function getField() {
        return ctrl.input.field || {};
      }

      function isDateField(field) {
        // Partial date functions get special handling to be treated as dates
        return field.category === 'partial_date' || field.data_type === 'Date' || field.data_type === 'Timestamp';
      }

    }
  });

})(angular, CRM.$, CRM._);
