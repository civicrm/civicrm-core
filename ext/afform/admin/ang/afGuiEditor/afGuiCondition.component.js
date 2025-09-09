(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiCondition', {
    bindings: {
      field: '<',
      clause: '<',
      format: '<',
      optionKey: '<',
      offset: '<'
    },
    templateUrl: '~/afGuiEditor/afGuiCondition.html',
    controller: function ($scope) {
      let ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;
      let conditionValue;
      let operatorCache = {};

      const allOperators= {
        '=': '=',
        '!=': '≠',
        '>': '>',
        '<': '<',
        '>=': '≥',
        '<=': '≤',
        'CONTAINS': ts('Contains'),
        'NOT CONTAINS': ts("Doesn't Contain"),
        'IN': ts('Is One Of'),
        'NOT IN': ts('Not One Of'),
        'LIKE': ts('Is Like'),
        'NOT LIKE': ts('Not Like'),
        'IS EMPTY': ts('Is Empty'),
        'IS NOT EMPTY': ts('Not Empty'),
        'IS NOT NULL': ts('Any Value'),
      };

      this.$onInit = function() {
        // Update legacy operator '==' to the new preferred '='
        if (getOperator() === '==') {
          setOperator('=');
        }
        $scope.$watch('$ctrl.field', updateOperators);
      };

      function getOperator() {
        return ctrl.clause[ctrl.offset];
      }

      function setOperator(op) {
        if (op !== getOperator()) {
          ctrl.clause[ctrl.offset] = op;
          updateOperators();
        }
      }

      // Getter for ng-model.
      // Returns a reference to avoid infinite loops in ngModel.watch
      function getValue() {
        let newVal = JSON.parse(ctrl.clause[1 + ctrl.offset]);
        if (!angular.equals(newVal, conditionValue)) {
          conditionValue = newVal;
        }
        return conditionValue;
      }

      function setValue(val) {
        ctrl.clause[1 + ctrl.offset] = JSON.stringify(val);
      }

      // Getter/setter for use with ng-model
      this.getSetValue = function(val) {
        if (arguments.length) {
          setValue(val);
        }
        return getValue();
      };

      // Return a list of operators allowed for the current field
      this.getOperators = function() {
        var field = ctrl.field || {},
          allowedOps = field.operators;
        if (!allowedOps && field.data_type === 'Boolean') {
          allowedOps = ['=', '!=', 'IS EMPTY', 'IS NOT NULL'];
        }
        if (!allowedOps && _.includes(['Boolean', 'Float', 'Date'], field.data_type)) {
          allowedOps = ['=', '!=', '<', '>', '<=', '>=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'IS EMPTY', 'IS NOT EMPTY'];
        }
        if (!allowedOps && (field.data_type === 'Array' || field.serialize)) {
          allowedOps = ['CONTAINS', 'NOT CONTAINS', 'IS EMPTY', 'IS NOT EMPTY'];
        }
        if (!allowedOps) {
          return allOperators;
        }
        var opKey = allowedOps.join();
        if (!operatorCache[opKey]) {
          operatorCache[opKey] = filterObjectByKeys(allOperators, allowedOps);
        }
        return operatorCache[opKey];
      };

      function filterObjectByKeys(obj, whitelist) {
        return Object.keys(obj)
          .filter(key => whitelist.includes(key))
          .reduce((filteredObj, key) => {
            filteredObj[key] = obj[key];
            return filteredObj;
          }, {});
      }

      // Ensures clause is using an operator that is allowed for the field
      function updateOperators() {
        if (!getOperator() || !(getOperator() in ctrl.getOperators())) {
          setOperator(Object.keys(ctrl.getOperators())[0]);
        }
      }

      // Returns false for 'IS NULL', 'IS EMPTY', etc. true otherwise.
      this.operatorTakesInput = function() {
        return getOperator().indexOf('IS ') !== 0;
      };

      this.changeClauseOperator = function() {
        // Add/remove value depending on whether operator allows for one
        if (!ctrl.operatorTakesInput()) {
          ctrl.clause.length = ctrl.offset + 1;
        } else {
          if (ctrl.clause.length === ctrl.offset + 1) {
            ctrl.clause.push('');
          }
          // Change multi/single value to/from an array
          var shouldBeArray = _.includes(['IN', 'NOT IN'], getOperator());
          if (!_.isArray(getValue()) && shouldBeArray) {
            setValue([]);
          } else if (_.isArray(getValue()) && !shouldBeArray) {
            setValue('');
          }
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
