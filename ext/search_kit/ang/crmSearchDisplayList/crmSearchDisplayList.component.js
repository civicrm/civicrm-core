(function(angular, $) {
  "use strict";

  angular.module('crmSearchDisplayList').component('crmSearchDisplayList', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      apiParams: '<',
      settings: '<',
      filters: '<',
      totalCount: '=?'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayList/crmSearchDisplayList.html',
    controller: function($scope, $element, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplayEditableTrait) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in required traits
        ctrl = angular.extend(this, CRM.utils.cloneDeep(searchDisplayBaseTrait), CRM.utils.cloneDeep(searchDisplayTasksTrait), CRM.utils.cloneDeep(searchDisplayEditableTrait));

      this.$onInit = function() {
        this.onPostRun.push(function(apiResults, status) {
          if (status === 'success' && ctrl.settings.section_group_by) {
            // Mark the first row of each group with its value, for the
            // template to render a header before it - see groupRows() for why
            // this is a client-side grouping, not a real SQL GROUP BY.
            ctrl.groupRows(ctrl.results, ctrl.settings.section_group_by).forEach(function(group) {
              group.rows[0].groupHeader = group.value;
            });
          }
        });

        this.initializeDisplay($scope, $element);
      };

    }
  });

})(angular, CRM.$);
