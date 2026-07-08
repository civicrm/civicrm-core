(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminGroupBy', {
    bindings: {
      apiEntity: '<',
      apiParams: '<',
    },
    require: {
      crmSearchAdmin: '^',
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminGroupBy.html',
    controller: function ($scope, $element) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.$onInit = () => {
        this.hasFunction = this.crmSearchAdmin.hasFunction;
      };

      this.fieldsForGroupBy = () => {
        return {
          results: this.crmSearchAdmin.getAllFields({api_entity: this.apiEntity, api_params: this.apiParams}, '', ['Field', 'Custom', 'Extra'], (key) => {
            return this.apiParams.groupBy?.includes(key);
          })
        };
      };

      this.addGroupBy = (columnName) => {
        this.apiParams.groupBy = this.apiParams.groupBy || [];
        if (columnName && !this.apiParams.groupBy.includes(columnName)) {
          this.apiParams.groupBy.push(columnName);
          this.crmSearchAdmin.reconcileAggregateColumns();
        }
      };

      this.changeGroupBy = (idx) => {
        if (!this.apiParams.groupBy[idx]) {
          this.apiParams.groupBy.splice(idx, 1);
        }
        this.crmSearchAdmin.reconcileAggregateColumns();
      };
    }
  });

})(angular, CRM.$, CRM._);
