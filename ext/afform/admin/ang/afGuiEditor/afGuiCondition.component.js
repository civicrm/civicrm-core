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
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;
      this.operators = [
        {
          "key": "==",
          "value": "=",
        },
        {
          "key": "!=",
          "value": "≠",
        },
        {
          "key": ">",
          "value": ">",
        },
        {
          "key": "<",
          "value": "<",
        },
        {
          "key": ">=",
          "value": "≥",
        },
        {
          "key": "<=",
          "value": "≤",
        }
      ];

      this.$onInit = function() {
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
        return JSON.parse(ctrl.clause[1 + ctrl.offset]);
      }

      function setValue(val) {
        ctrl.clause[1 + ctrl.offset] = JSON.stringify(val);
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

      // Return a list of operators allowed for the current field
      this.getOperators = function() {
        return ctrl.operators;
      };

      // Ensures clause is using an operator that is allowed for the field
      function updateOperators() {
        if ((!getOperator() || !_.includes(_.pluck(ctrl.getOperators(), 'key'), getOperator()))) {
          setOperator(ctrl.getOperators()[0].key);
        }
      }

      this.changeClauseOperator = function() {
      };

    }
  });

})(angular, CRM.$, CRM._);
