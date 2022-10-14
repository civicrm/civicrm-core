(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchClause', {
    bindings: {
      fields: '<',
      clauses: '<',
      format: '@',
      op: '@',
      allowFunctions: '<',
      skip: '<',
      label: '@',
      hideLabel: '@',
      placeholder: '<',
      deleteGroup: '&'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchClause.html',
    controller: function ($scope, $element, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        meta = {};
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

      // Gets the first arg of type "field"
      function getFirstArgFromExpr(expr) {
        if (!(expr in meta)) {
          var args = searchMeta.parseExpr(expr).args;
          meta[expr] = _.findWhere(args, {type: 'field'});
        }
        return meta[expr] || {};
      }

      this.getField = function(expr) {
        return getFirstArgFromExpr(expr).field;
      };

      this.getFieldOrFunction = function(expr) {
        if (ctrl.hasFunction(expr)) {
          return searchMeta.parseExpr(expr).fn;
        }
        return ctrl.getField(expr);
      };

      this.getOptionKey = function(expr) {
        var arg = getFirstArgFromExpr(expr);
        return arg.suffix ? arg.suffix.slice(1) : 'id';
      };

      this.hasFunction = function(expr) {
        return expr.indexOf('(') > -1;
      };

      this.areFunctionsAllowed = function(expr) {
        return this.allowFunctions && ctrl.getField(expr).type !== 'Filter';
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
