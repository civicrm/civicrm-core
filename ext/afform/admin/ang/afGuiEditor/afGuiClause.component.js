(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiClause', {
    bindings: {
      fields: '<',
      fieldDefns: '<',
      clauses: '<',
      skip: '<',
      op: '@',
      label: '@',
      hideLabel: '@',
      placeholder: '<',
      deleteGroup: '&'
    },
    templateUrl: '~/afGuiEditor/afGuiClause.html',
    controller: function ($scope, $element) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.conjunctions = {AND: ts('And'), OR: ts('Or'), NOT: ts('Not')};
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
        return ctrl.fieldDefns[expr];
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
          ctrl.clauses.push([value, '=', '""']);
        }
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

    }
  });

})(angular, CRM.$, CRM._);
