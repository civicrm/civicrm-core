(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminUiParams', {
    bindings: {
      savedSearch: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminUiParams.html',
    controller: function ($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.$onInit = () => {
        const entity = searchMeta.getEntity(this.savedSearch.api_entity);
        this.uiParams = entity.ui_params ?? [];
      };

    }
  });

})(angular, CRM.$, CRM._);
