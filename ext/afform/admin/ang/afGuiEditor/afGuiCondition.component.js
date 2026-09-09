(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiCondition', {
    bindings: {
      field: '<',
      clause: '<',
      format: '<',
      optionKey: '<',
      offset: '<',
      allFields: '<',
      fieldDefns: '<'
    },
    templateUrl: '~/afGuiEditor/afGuiCondition.html',
    controller: function ($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
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
        'BETWEEN': ts('Is Between'),
        'NOT BETWEEN': ts('Not Between'),
        'LIKE': ts('Is Like'),
        'NOT LIKE': ts('Not Like'),
        'IS EMPTY': ts('Is Empty'),
        'IS NOT EMPTY': ts('Not Empty'),
        'IS NOT NULL': ts('Any Value'),
        'IS NULL': ts('No Value'),
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

      function getValueAt(index) {
        const currentVal = getValue();
        return Array.isArray(currentVal) ? currentVal[index] : undefined;
      }

      function setValueAt(index, val) {
        const newVal = Array.isArray(getValue()) ? getValue().slice() : ['', ''];
        newVal[index] = val;
        setValue(newVal);
      }

      // Getter/setter for one side of a BETWEEN/NOT BETWEEN range, for use with ng-model
      function getSetValueAt(index) {
        return function(val) {
          if (arguments.length) {
            setValueAt(index, val);
          }
          return getValueAt(index);
        };
      }
      this.getSetValueAt0 = getSetValueAt(0);
      this.getSetValueAt1 = getSetValueAt(1);

      this.isBetween = function() {
        return ['BETWEEN', 'NOT BETWEEN'].includes(getOperator());
      };

      this.isDateField = function() {
        return (ctrl.field || {}).data_type === 'Date';
      };

      // Recognizes the "@now"/"@now±N" (relative to today) and "@field:<path>"/"@field:<path>±N"
      // (relative to another date field) tokens used in place of a literal date value.
      const NOW_PATTERN = /^@now([+-]\d+)?$/;
      // Path may be empty here (unlike the runtime patterns) - the admin may have switched to
      // "relative to another field" mode but not picked a field yet.
      const FIELD_PATTERN = /^@field:([^+-]*)([+-]\d+)?$/;

      function parseDateToken(val) {
        if (typeof val === 'string') {
          let match = val.match(NOW_PATTERN);
          if (match) {
            return {mode: 'now', fieldPath: '', offset: match[1] ? parseInt(match[1], 10) : 0};
          }
          match = val.match(FIELD_PATTERN);
          if (match) {
            return {mode: 'field', fieldPath: match[1], offset: match[2] ? parseInt(match[2], 10) : 0};
          }
        }
        return {mode: 'literal', fieldPath: '', offset: 0};
      }

      function formatDateToken(mode, fieldPath, offset) {
        if (mode === 'now') {
          return offset ? ('@now' + (offset > 0 ? '+' : '') + offset) : '@now';
        }
        if (mode === 'field') {
          const base = '@field:' + (fieldPath || '');
          return offset ? (base + (offset > 0 ? '+' : '') + offset) : base;
        }
        return '';
      }

      // Builds getter/setter functions (for use with ng-model) for the "mode"/"offset"/"field"
      // of a value that can be a literal date, "relative to today", or "relative to another
      // date field" - given plain get/set functions for that underlying value.
      function makeDateValueControl(getVal, setVal) {
        return {
          getSetMode: function(val) {
            if (arguments.length) {
              const current = parseDateToken(getVal());
              setVal(val === 'literal' ? '' : formatDateToken(val, current.fieldPath, current.offset));
            }
            return parseDateToken(getVal()).mode;
          },
          getSetOffset: function(val) {
            const current = parseDateToken(getVal());
            if (arguments.length) {
              setVal(formatDateToken(current.mode, current.fieldPath, parseInt(val, 10) || 0));
            }
            return current.offset;
          },
          getSetFieldPath: function(val) {
            const current = parseDateToken(getVal());
            if (arguments.length) {
              setVal(formatDateToken('field', val, current.offset));
            }
            return current.fieldPath;
          }
        };
      }

      const singleValueControl = makeDateValueControl(getValue, setValue);
      this.getSetMode = singleValueControl.getSetMode;
      this.getSetOffset = singleValueControl.getSetOffset;
      this.getSetFieldPath = singleValueControl.getSetFieldPath;

      const value0Control = makeDateValueControl(() => getValueAt(0), (val) => setValueAt(0, val));
      this.getSetMode0 = value0Control.getSetMode;
      this.getSetOffset0 = value0Control.getSetOffset;
      this.getSetFieldPath0 = value0Control.getSetFieldPath;

      const value1Control = makeDateValueControl(() => getValueAt(1), (val) => setValueAt(1, val));
      this.getSetMode1 = value1Control.getSetMode;
      this.getSetOffset1 = value1Control.getSetOffset;
      this.getSetFieldPath1 = value1Control.getSetFieldPath;

      // Date fields available to anchor a "relative to another field" value to (excludes the
      // field this condition is testing, and non-Date fields).
      this.getDateFieldOptions = function() {
        const currentPath = ctrl.clause[0];
        const defns = ctrl.fieldDefns || {};
        return (ctrl.allFields || []).reduce((groups, group) => {
          const children = (group.children || []).filter((item) => {
            const defn = defns[item.id];
            return defn && defn.data_type === 'Date' && item.id !== currentPath;
          });
          if (children.length) {
            groups.push({text: group.text, children: children});
          }
          return groups;
        }, []);
      };

      // Return a list of operators allowed for the current field
      this.getOperators = () => {
        const field = ctrl.field || {};
        let allowedOps = field.operators;
        if (!allowedOps && field.data_type === 'Boolean') {
          allowedOps = ['=', '!=', 'IS EMPTY', 'IS NOT NULL', 'IS NULL'];
        }
        if (!allowedOps && ['Boolean', 'Float', 'Date'].includes(field.data_type)) {
          allowedOps = ['=', '!=', '<', '>', '<=', '>=', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'IS EMPTY', 'IS NOT EMPTY'];
        }
        if (!allowedOps && (field.data_type === 'Array' || field.serialize)) {
          allowedOps = ['CONTAINS', 'NOT CONTAINS', 'IS EMPTY', 'IS NOT EMPTY'];
        }
        if (!allowedOps) {
          return allOperators;
        }
        const opKey = allowedOps.join();
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
          const shouldBeArray = ['IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN'].includes(getOperator());
          if (!Array.isArray(getValue()) && shouldBeArray) {
            setValue([]);
          } else if (Array.isArray(getValue()) && !shouldBeArray) {
            setValue('');
          }
          // BETWEEN/NOT BETWEEN always take exactly 2 values
          if (ctrl.isBetween() && getValue().length !== 2) {
            setValue([getValue()[0] || '', getValue()[1] || '']);
          }
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
