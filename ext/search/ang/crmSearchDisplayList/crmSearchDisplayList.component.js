(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayList').component('crmSearchDisplayList', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      apiParams: '<',
      settings: '<',
      filters: '<'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayList/crmSearchDisplayList.html',
    controller: function($scope, crmApi4, searchDisplayUtils) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.page = 1;
      this.rowCount = null;

      this.$onInit = function() {
        this.sort = this.settings.sort ? _.cloneDeep(this.settings.sort) : [];
        $scope.displayUtils = searchDisplayUtils;

        if (this.afFieldset) {
          $scope.$watch(this.afFieldset.getFieldData, refresh, true);
        }
        $scope.$watch('$ctrl.filters', refresh, true);
      };

      this.getResults = _.debounce(function() {
        searchDisplayUtils.getResults(ctrl);
      }, 100);

      function refresh() {
        ctrl.page = 1;
        ctrl.rowCount = null;
        ctrl.getResults();
      }

      $scope.formatResult = function(row, col) {
        var value = row[col.key],
          formatted = searchDisplayUtils.formatSearchValue(row, col, value),
          output = '';
        if (formatted.length || (col.label && col.forceLabel)) {
          if (col.label && (formatted.length || col.forceLabel)) {
            output += '<label>' + _.escape(col.label) + '</label> ';
          }
          if (formatted.length) {
            output += (col.prefix || '') + formatted + (col.suffix || '');
          }
        }
        return output;
      };

    }
  });

})(angular, CRM.$, CRM._);
