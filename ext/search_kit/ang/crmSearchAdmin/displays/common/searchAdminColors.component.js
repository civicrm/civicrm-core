(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminColors', {
    bindings: {
      item: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/displays/common/searchAdminColors.html',
    controller: function($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        const savedSearch = ctrl.crmSearchAdmin.savedSearch;
        const field = searchMeta.getField(ctrl.item.key, savedSearch);
        ctrl.colorField = field ? searchMeta.getColorField(ctrl.item.key, savedSearch) : null;
        if (ctrl.colorField) {
          ctrl.colorLabel = ts('Use %1 color', {1: field.label});
        }
      };

      this.toggleColor = function() {
        if (ctrl.item.colors && ctrl.item.colors.length) {
          delete ctrl.item.colors;
        }
        else {
          ctrl.item.colors = [{field: ctrl.colorField}];
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
