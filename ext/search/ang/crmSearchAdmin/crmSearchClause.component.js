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
      deleteGroup: '&'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchClause.html',
    controller: function ($scope, $element, $timeout, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this,
        meta = {};
      this.conjunctions = {AND: ts('And'), OR: ts('Or'), NOT: ts('Not')};
      this.operators = CRM.crmSearchAdmin.operators;
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
      };

      this.getField = function(expr) {
        if (!meta[expr]) {
          meta[expr] = searchMeta.parseExpr(expr);
        }
        return meta[expr].field;
      };

      this.getOptionKey = function(expr) {
        if (!meta[expr]) {
          meta[expr] = searchMeta.parseExpr(expr);
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

      this.addClause = function() {
        $timeout(function() {
          if (ctrl.newClause) {
            ctrl.clauses.push([ctrl.newClause, '=', '']);
            ctrl.newClause = null;
          }
        });
      };

      this.deleteRow = function(index) {
        ctrl.clauses.splice(index, 1);
      };

      // Remove empty values
      this.changeClauseField = function(clause, index) {
        if (clause[0] === '') {
          ctrl.deleteRow(index);
        }
      };

      // Add/remove value if operator allows for one
      this.changeClauseOperator = function(clause) {
        if (_.contains(clause[1], 'NULL')) {
          clause.length = 2;
        } else if (clause.length === 2) {
          clause.push('');
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
