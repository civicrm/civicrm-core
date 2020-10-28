(function(angular, $, _) {
  "use strict";

  angular.module('searchAdmin').component('searchAdminDisplayTable', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/searchAdmin/displays/searchAdminDisplayTable.html',
    controller: function($scope, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      function fieldToColumn(fieldExpr) {
        var info = searchMeta.parseExpr(fieldExpr);
        return {
          expr: fieldExpr,
          label: ctrl.getFieldLabel(fieldExpr),
          dataType: (info.fn && info.fn.name === 'COUNT') ? 'Integer' : info.field.data_type
        };
      }

      this.sortableOptions = {
        connectWith: '.crm-search-admin-table-columns',
        containment: '.crm-search-admin-table-columns-wrapper'
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
        ctrl.getFieldLabel = ctrl.crmSearchAdmin.getFieldLabel;
        if (!ctrl.display.settings.columns) {
          ctrl.display.settings.columns = _.transform(ctrl.apiParams.select, function(columns, fieldExpr) {
            columns.push(fieldToColumn(fieldExpr));
          });
          ctrl.hiddenColumns = [];
        } else {
          var activeColumns = _.collect(ctrl.display.settings.columns, 'expr');
          ctrl.hiddenColumns = _.transform(ctrl.apiParams.select, function(hiddenColumns, fieldExpr) {
            if (!_.includes(activeColumns, fieldExpr)) {
              hiddenColumns.push(fieldToColumn(fieldExpr));
            }
          });
          _.each(activeColumns, function(fieldExpr, index) {
            if (!_.includes(ctrl.apiParams.select, fieldExpr)) {
              ctrl.display.settings.columns.splice(index, 1);
            }
          });
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
