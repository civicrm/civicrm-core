(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminEntitySetQuery', {
    bindings: {
      entitySet: '<',
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminEntitySetQuery.html',
    controller: function ($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.$onInit = () => {
        this.apiEntity = this.entitySet[1];
        this.apiParams = this.entitySet[3];

        this.entityInfo = searchMeta.getEntity(this.entitySet[1]);
      };

      this.fieldsForWhere = () => {
        return {results: this.crmSearchAdmin.getAllFields({api_entity: this.apiEntity, api_params: this.apiParams}, ':name')};
      };

      this.paramExists = (param) => {
        return this.entityInfo.params?.includes(param);
      };

    }
  });

})(angular, CRM.$, CRM._);
