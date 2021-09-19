(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchClause', {
    bindings: {
      fields: '<',
      clauses: '<',
      format: '@',
      op: '@',
      skip: '<',
      label: '@',
      hideLabel: '@',
      placeholder: '<',
      deleteGroup: '&'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchClause.html',
    controller: function ($scope, $element, $timeout, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        meta = {};
      this.conjunctions = {AND: ts('And'), OR: ts('Or'), NOT: ts('Not')};
      this.operators = {};
      this.sortOptions = {
        axis: 'y',
        connectWith: '.api4-clause-group-sortable',
        containment: $element.closest('.api4-clause-fieldset'),
        over: onSortOver,
        start: onSort,
        stop: onSort
      };

      this.$onInit = function() {
        ctrl.hasParent = !!$element.attr('delete-group');
        _.each(ctrl.clauses, updateOperators);
      };

      // Return a list of operators allowed for the field in a given clause
      this.getOperators = function(clause) {
        var field = ctrl.getField(clause[0]);
        if (!field || !field.operators) {
          return CRM.crmSearchAdmin.operators;
        }
        var opKey = field.operators.join();
        if (!ctrl.operators[opKey]) {
          ctrl.operators[opKey] = _.filter(CRM.crmSearchAdmin.operators, function(operator) {
            return _.includes(field.operators, operator.key);
          });
        }
        return ctrl.operators[opKey];
      };

      // Ensures a clause is using an operator that is allowed for the field
      function updateOperators(clause) {
        // Recurse into AND/OR/NOT groups
        if (ctrl.conjunctions[clause[0]]) {
          _.each(clause[1], updateOperators);
        }
        else if (!ctrl.skip && (!clause[1] || !_.includes(_.pluck(ctrl.getOperators(clause), 'key'), clause[1]))) {
          clause[1] = ctrl.getOperators(clause)[0].key;
          ctrl.changeClauseOperator(clause);
        }
      }

      this.getField = function(expr) {
        if (!meta[expr]) {
          meta[expr] = searchMeta.parseExpr(expr).args[0];
        }
        return meta[expr].field;
      };

      this.getOptionKey = function(expr) {
        if (!meta[expr]) {
          meta[expr] = _.findWhere(searchMeta.parseExpr(expr).args, {type: 'field'});
        }
        return meta[expr].suffix ? meta[expr].suffix.slice(1) : 'id';
      };

      this.addGroup = function(op) {
        ctrl.clauses.push([op, []]);
      };

      function onSort(event, ui) {
        $($element).closest('.api4-clause-fieldset').toggleClass('api4-sorting', event.type === 'sortstart');
        $('.api4-input.form-inline').css('margin-left', '');
      }

      // Indent clause while dragging between nested groups
      function onSortOver(event, ui) {
        var offset = 0;
        if (ui.sender) {
          offset = $(ui.placeholder).offset().left - $(ui.sender).offset().left;
        }
        $('.api4-input.form-inline.ui-sortable-helper').css('margin-left', '' + offset + 'px');
      }

      this.addClause = function(value) {
        if (value) {
          var newIndex = ctrl.clauses.length;
          ctrl.clauses.push([value, '=', '']);
          updateOperators(ctrl.clauses[newIndex]);
        }
      };

      this.deleteRow = function(index) {
        ctrl.clauses.splice(index, 1);
      };

      // Remove empty values
      this.changeClauseField = function(clause, index) {
        if (clause[0] === '') {
          ctrl.deleteRow(index);
        } else {
          updateOperators(clause);
        }
      };

      // Returns false for 'IS NULL', 'IS EMPTY', etc. true otherwise.
      this.operatorTakesInput = function(operator) {
        return operator.indexOf('IS ') !== 0;
      };

      this.changeClauseOperator = function(clause) {
        // Add/remove value depending on whether operator allows for one
        if (!ctrl.operatorTakesInput(clause[1])) {
          clause.length = 2;
        } else {
          if (clause.length === 2) {
            clause.push('');
          }
          // Change multi/single value to/from an array
          var shouldBeArray = (clause[1] === 'IN' || clause[1] === 'NOT IN' || clause[1] === 'BETWEEN' || clause[1] === 'NOT BETWEEN');
          if (!_.isArray(clause[2]) && shouldBeArray) {
            clause[2] = [];
          } else if (_.isArray(clause[2]) && !shouldBeArray) {
            clause[2] = '';
          }
          if (clause[1] === 'BETWEEN' || clause[1] === 'NOT BETWEEN') {
            clause[2].length = 2;
          }
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
