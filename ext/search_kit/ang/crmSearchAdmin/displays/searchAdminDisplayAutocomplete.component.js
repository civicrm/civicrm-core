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
        ctrl = this,
        colTypes = [];

      this.getColTypes = function() {
        return colTypes;
      };

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            sort: ctrl.parent.getDefaultSort(),
            columns: []
          };
          var labelField = searchMeta.getEntity(ctrl.apiEntity).label_field;
          _.each([labelField, 'description'], function(field) {
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
