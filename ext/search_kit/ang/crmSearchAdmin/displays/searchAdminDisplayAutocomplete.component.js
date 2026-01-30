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
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.isAdmin = CRM.checkPerm('administer CiviCRM');

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            sort: ctrl.parent.getDefaultSort(),
            columns: []
          };
          const searchFields = searchMeta.getEntity(ctrl.apiEntity).search_fields || [];
          searchFields.push('description');
          searchFields.forEach((field) => {
            if (ctrl.parent.savedSearch.api_params.select.includes(field)) {
              ctrl.display.settings.columns.push(searchMeta.fieldToColumn(field, {}));
            }
          });
        }
        ctrl.parent.initColumns({});
        ctrl.display.settings.searchFields = ctrl.display.settings.searchFields || [];
        if (!ctrl.display.settings.searchFields.length) {
          const baseEntity = searchMeta.getBaseEntity();
          if (searchMeta.getField('id')) {
            ctrl.display.settings.searchFields.push('id');
          }
          if (baseEntity.search_fields && baseEntity.search_fields.length) {
            ctrl.display.settings.searchFields.push(...baseEntity.search_fields);
          }
        }
        // Ensure array is unique
        ctrl.display.settings.searchFields = _.uniq(ctrl.display.settings.searchFields);
      };

    }
  });

})(angular, CRM.$, CRM._);
