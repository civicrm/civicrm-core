(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayAutocomplete', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayAutocomplete.html',
    controller: function($scope, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            sort: ctrl.parent.getDefaultSort(),
            columns: []
          };
          var searchFields = searchMeta.getEntity(ctrl.apiEntity).search_fields || [];
          searchFields.push('description');
          searchFields.forEach((field) => {
            if (_.includes(ctrl.parent.savedSearch.api_params.select, field)) {
              ctrl.display.settings.columns.push(searchMeta.fieldToColumn(field, {}));
            }
          });
        }
        ctrl.parent.initColumns({});
      };

    }
  });

})(angular, CRM.$, CRM._);
