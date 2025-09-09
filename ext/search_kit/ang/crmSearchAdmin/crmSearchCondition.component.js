(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchCondition', {
    bindings: {
      field: '<',
      clause: '<',
      format: '<',
      optionKey: '<',
      fields: '<',
      offset: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchCondition.html',
    controller: function ($scope) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      this.operators = {};

      this.$onInit = function() {
        if (this.fields) {
          let val = getValue();
          // WHERE clause has an explicit flag if input type is a field
          if (this.format !== 'json') {
            this.inputMode = this.clause[2 + ctrl.offset] ? 'field' : 'value';
          }
          // ON clause will be quoted json-style if not a field
          else {
            if (typeof val === 'string' && /^[a-zA-Z]/.test(val)) {
              this.inputMode = 'field';
            }
          }
        }
        this.inputMode = this.inputMode || 'value';
        $scope.$watch('$ctrl.field', updateOperators);
      };

      function getOperator() {
        return ctrl.clause[ctrl.offset];
      }

      function setOperator(op) {
        if (op !== getOperator()) {
          ctrl.clause[ctrl.offset] = op;
          ctrl.changeClauseOperator();
        }
      }

      function getValue() {
        return ctrl.clause[1 + ctrl.offset];
      }

      function setValue(val) {
        ctrl.clause[1 + ctrl.offset] = val;
      }

      // Getter/setter for use with ng-model
      this.getSetOperator = function(op) {
        if (arguments.length) {
          setOperator(op);
        }
        return getOperator();
      };

      // Getter/setter for use with ng-model
      this.getSetValue = function(val) {
        if (arguments.length) {
          setValue(val);
        }
        return getValue();
      };

      // ngChange handler for the field/value mode toggle
      this.changeInputMode = function() {
        setValue('');
        if (ctrl.format !== 'json') {
          if (ctrl.inputMode === 'field') {
            this.clause[2 + ctrl.offset] = true;
          } else {
            delete this.clause[2 + ctrl.offset];
          }
        }
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
          allowedOps = ['CONTAINS', 'NOT CONTAINS', 'CONTAINS ONE OF', 'NOT CONTAINS ONE OF', 'IS EMPTY', 'IS NOT EMPTY'];
        }
        if (!allowedOps) {
          return CRM.crmSearchAdmin.operators;
        }
        var opKey = allowedOps.join();
        if (!ctrl.operators[opKey]) {
          ctrl.operators[opKey] = _.filter(CRM.crmSearchAdmin.operators, function(operator) {
            return _.includes(allowedOps, operator.key);
          });
        }
        return ctrl.operators[opKey];
      };

      // Ensures clause is using an operator that is allowed for the field
      function updateOperators() {
        if ((!getOperator() || !_.includes(_.pluck(ctrl.getOperators(), 'key'), getOperator()))) {
          setOperator(ctrl.getOperators()[0].key);
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
          var shouldBeArray = _.includes(['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'], getOperator());
          if (!_.isArray(getValue()) && shouldBeArray) {
            setValue([]);
          } else if (_.isArray(getValue()) && !shouldBeArray) {
            setValue('');
          }
          if (_.includes(['BETWEEN', 'NOT BETWEEN'], getOperator())) {
            getValue().length = 2;
          }
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
