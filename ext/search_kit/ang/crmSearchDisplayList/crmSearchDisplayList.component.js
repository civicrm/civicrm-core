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
    controller: function($scope, $element, crmApi4, searchDisplayUtils) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.page = 1;
      this.rowCount = null;

      this.$onInit = function() {
        this.sort = this.settings.sort ? _.cloneDeep(this.settings.sort) : [];
        $scope.displayUtils = searchDisplayUtils;

        // If search is embedded in contact summary tab, display count in tab-header
        var contactTab = $element.closest('.crm-contact-page .ui-tabs-panel').attr('id');
        if (contactTab) {
          var unwatchCount = $scope.$watch('$ctrl.rowCount', function(rowCount) {
            if (typeof rowCount === 'number') {
              unwatchCount();
              CRM.tabHeader.updateCount(contactTab.replace('contact-', '#tab_'), rowCount);
            }
          });
        }

        if (this.afFieldset) {
          $scope.$watch(this.afFieldset.getFieldData, onChangeFilters, true);
        }
        $scope.$watch('$ctrl.filters', onChangeFilters, true);
      };

      this.getResults = _.debounce(function() {
        searchDisplayUtils.getResults(ctrl);
      }, 100);

      // Refresh current page
      this.refresh = function(row) {
        searchDisplayUtils.getResults(ctrl);
      };

      function onChangeFilters() {
        ctrl.page = 1;
        ctrl.rowCount = null;
        ctrl.getResults();
      }

      this.formatFieldValue = function(rowData, col) {
        return searchDisplayUtils.formatDisplayValue(rowData, col.key, ctrl.settings.columns);
      };

    }
  });

})(angular, CRM.$, CRM._);
