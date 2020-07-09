(function(angular, $, _) {
  "use strict";

  angular.module('search').directive('crmSearchClause', function() {
    return {
      scope: {
        data: '<crmSearchClause'
      },
      templateUrl: '~/search/crmSearchClause.html',
      controller: function ($scope, $element, $timeout) {
        var ts = $scope.ts = CRM.ts();
        var ctrl = $scope.$ctrl = this;
        this.conjunctions = {AND: ts('And'), OR: ts('Or'), NOT: ts('Not')};
        this.operators = CRM.vars.search.operators;
        this.sortOptions = {
          axis: 'y',
          connectWith: '.api4-clause-group-sortable',
          containment: $element.closest('.api4-clause-fieldset'),
          over: onSortOver,
          start: onSort,
          stop: onSort
        };

        this.addGroup = function(op) {
          $scope.data.clauses.push([op, []]);
        };

        this.removeGroup = function() {
          $scope.data.groupParent.splice($scope.data.groupIndex, 1);
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
              $scope.data.clauses.push([ctrl.newClause, '=', '']);
              ctrl.newClause = null;
            }
          });
        };
        $scope.$watch('data.clauses', function(values) {
          // Iterate in reverse order so index doesn't get out-of-sync during splice
          _.forEachRight(values, function(clause, index) {
            // Remove empty values
            if (index >= ($scope.data.skip  || 0)) {
              if (typeof clause !== 'undefined' && !clause[0]) {
                values.splice(index, 1);
              }
              // Add/remove value if operator allows for one
              else if (typeof clause[1] === 'string' && _.contains(clause[1], 'NULL')) {
                clause.length = 2;
              } else if (typeof clause[1] === 'string' && clause.length === 2) {
                clause.push('');
              }
            }
          });
        }, true);
      }
    };
  });

})(angular, CRM.$, CRM._);
