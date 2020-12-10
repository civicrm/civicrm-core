(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayTable', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdminDisplay: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayTable.html',
    controller: function($scope, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;
      this.getFieldLabel = searchMeta.getDefaultLabel;

      this.sortableOptions = {
        connectWith: '.crm-search-admin-edit-columns',
        containment: '.crm-search-admin-edit-columns-wrapper'
      };

      this.removeCol = function(index) {
        ctrl.hiddenColumns.push(ctrl.display.settings.columns[index]);
        ctrl.display.settings.columns.splice(index, 1);
      };

      this.restoreCol = function(index) {
        ctrl.display.settings.columns.push(ctrl.hiddenColumns[index]);
        ctrl.hiddenColumns.splice(index, 1);
      };

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            limit: 20,
            pager: true
          };
        }
        ctrl.hiddenColumns = ctrl.crmSearchAdminDisplay.initColumns();
        ctrl.links = ctrl.crmSearchAdminDisplay.getLinks();
      };

    }
  });

})(angular, CRM.$, CRM._);
