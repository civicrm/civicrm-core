(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminGroup', {
    bindings: {
      savedSearch: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminGroup.html',
    controller: function ($scope, $element, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.getEntity = searchMeta.getEntity;
      this.getField = searchMeta.getField;

      this.smartGroupColumns = [];

      this.$onInit = () => {
        searchMeta.loadFieldOptions(['Group']);
        this.smartGroup = this.savedSearch.groups[0];

        this.smartGroupColumns = searchMeta.getSmartGroupColumns(this.savedSearch);
        const smartGroupColIds = this.smartGroupColumns.map(col => col.id);
        if (smartGroupColIds.length &&
          !smartGroupColIds.some(colId => colId === this.savedSearch.api_params.select[0])
        ) {
          this.savedSearch.api_params.select.unshift(smartGroupColIds[0]);
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
