(function(angular, $) {
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

      this.searchInfo = {};

      this.$onInit = () => {
        this.apiParams.where = this.apiParams.where || [];
        if (this.crmSearchAdmin.paramExists('having')) {
          this.apiParams.having = this.apiParams.having || [];
        }
        this.searchInfo.api_entity = this.apiEntity;
        this.searchInfo.api_params = this.apiParams;
      };

      this.fieldsForWhere = () => {
        return {results: this.crmSearchAdmin.getAllFields(this.searchInfo, ':name')};
      };

      this.fieldsForHaving = () => {
        return {results: this.crmSearchAdmin.getSelectFields(this.searchInfo)};
      };
    }
  });

})(angular, CRM.$);
