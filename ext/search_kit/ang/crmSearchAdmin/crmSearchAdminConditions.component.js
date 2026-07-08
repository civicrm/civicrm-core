(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminConditions', {
    bindings: {
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminConditions.html',
    controller: function ($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.$onInit = () => {
        this.apiParams.where = this.apiParams.where || [];
        if (this.crmSearchAdmin.paramExists('having')) {
          this.apiParams.having = this.apiParams.having || [];
        }
      }

      this.fieldsForWhere = () => {
        return {results: this.crmSearchAdmin.getAllFields(':name')};
      };

      this.fieldsForHaving = () => {
        return {results: this.crmSearchAdmin.getSelectFields()};
      };
    }
  });

})(angular, CRM.$, CRM._);
