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
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.$onInit = () => {
        const savedSearch = this.crmSearchAdmin.savedSearch;
        const field = searchMeta.getField(this.item.key, savedSearch);
        this.colorField = field ? searchMeta.getColorField(this.item.key, savedSearch) : null;
        if (this.colorField) {
          this.colorLabel = ts('Use %1 color', {1: field.label});
        }
      };

      this.toggleColor = () => {
        if (this.item.colors && this.item.colors.length) {
          delete this.item.colors;
        }
        else {
          this.item.colors = [{field: this.colorField}];
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
