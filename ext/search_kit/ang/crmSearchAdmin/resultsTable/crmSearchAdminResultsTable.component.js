(function(angular, $, _) {
  "use strict";

  // Specialized searchDisplay, only used by Admins
  angular.module('crmSearchAdmin').component('crmSearchAdminResultsTable', {
    bindings: {
      search: '<',
      debug: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/resultsTable/crmSearchAdminResultsTable.html',
    controller: function($scope, $element, searchMeta, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in copies of traits to this controller
        ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayTasksTrait), _.cloneDeep(searchDisplaySortableTrait));

      function buildSettings() {
        ctrl.apiEntity = ctrl.search.api_entity;
        ctrl.settings = _.cloneDeep(CRM.crmSearchAdmin.defaultDisplay.settings);
        ctrl.settings.button = ts('Search');
        // The default-display settings contain just one column (the last one, with the links menu)
        ctrl.settings.columns = _.transform(ctrl.search.api_params.select, function(columns, fieldExpr) {
          columns.push(searchMeta.fieldToColumn(fieldExpr, {label: true, sortable: true}));
        }).concat(ctrl.settings.columns);
        ctrl.debug.apiParams = JSON.stringify(ctrl.search.api_params, null, 2);
        delete ctrl.debug.sql;
        delete ctrl.debug.timeIndex;
        ctrl.results = null;
        ctrl.rowCount = null;
        ctrl.page = 1;
        ctrl.selectNone();
      }

      this.$onInit = function() {
        buildSettings();
        this.initializeDisplay($scope, $element);
        $scope.$watch('$ctrl.search.api_params', buildSettings, true);
      };

      // Add callbacks for pre & post run
      this.onPreRun.push(function(apiCalls) {
        // So the raw SQL can be shown in the "Query Info" tab
        apiCalls.run[2].debug = true;
      });

      this.onPostRun.push(function(apiResults) {
        // Add debug output (e.g. raw SQL) to the "Query Info" tab
        ctrl.debug.sql = apiResults.run.debug.sql;
        ctrl.debug.timeIndex = apiResults.run.debug.timeIndex;
      });

      $scope.sortableColumnOptions = {
        axis: 'x',
        handle: '.crm-draggable',
        update: function(e, ui) {
          // Don't allow items to be moved to position 0 if locked
          if (!ui.item.sortable.dropindex && ctrl.crmSearchAdmin.groupExists) {
            ui.item.sortable.cancel();
          }
        }
      };

      $scope.fieldsForSelect = function() {
        return ctrl.crmSearchAdmin.fieldsForSelect();
      };

      $scope.addColumn = function(col) {
        ctrl.crmSearchAdmin.addParam('select', col);
      };

      $scope.removeColumn = function(index) {
        ctrl.crmSearchAdmin.clearParam('select', index);
      };

      $scope.getColumnLabel = function(index) {
        return searchMeta.getDefaultLabel(ctrl.search.api_params.select[index], ctrl.search);
      };

    }
  });

})(angular, CRM.$, CRM._);
