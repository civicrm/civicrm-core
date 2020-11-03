(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayTable', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayTable.html',
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
        ctrl.getFieldLabel = ctrl.crmSearchAdmin.getFieldLabel;
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            limit: 20,
            pager: true
          };
        }
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
        ctrl.links = _.cloneDeep(searchMeta.getEntity(ctrl.apiEntity).paths || []);
        _.each(ctrl.apiParams.join, function(join) {
          var joinName = join[0].split(' AS '),
            joinEntity = searchMeta.getEntity(joinName[0]);
          _.each(joinEntity.paths, function(path) {
            var link = _.cloneDeep(path);
            link.path = link.path.replace(/\[/g, '[' + joinName[1] + '.');
            ctrl.links.push(link);
          });
        });
      };

    }
  });

})(angular, CRM.$, CRM._);
