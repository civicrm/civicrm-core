(function(angular, $, _) {
  "use strict";

  // Specialized searchDisplay, only used by Admins
  angular.module('crmSearchAdmin').component('crmSearchAdminResultsTable', {
    bindings: {
      search: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/resultsTable/crmSearchAdminResultsTable.html',
    controller: function($scope, $element, searchMeta, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in traits to this controller
        ctrl = angular.extend(this, searchDisplayBaseTrait, searchDisplayTasksTrait, searchDisplaySortableTrait);

      function buildSettings() {
        ctrl.apiEntity = ctrl.search.api_entity;
        ctrl.settings = _.cloneDeep(CRM.crmSearchAdmin.defaultDisplay.settings);
        ctrl.settings.button = ts('Search');
        // The default-display settings contain just one column (the last one, with the links menu)
        ctrl.settings.columns = _.transform(ctrl.search.api_params.select, function(columns, fieldExpr) {
          columns.push(searchMeta.fieldToColumn(fieldExpr, {label: true, sortable: true}));
        }).concat(ctrl.settings.columns);
        ctrl.debug = {
          apiParams: JSON.stringify(ctrl.search.api_params, null, 2)
        };
        ctrl.results = null;
        ctrl.rowCount = null;
        ctrl.page = 1;
      }

      this.$onInit = function() {
        buildSettings();
        this.initializeDisplay($scope, $element);
        $scope.$watch('$ctrl.search.api_params', buildSettings, true);
      };

      // Add callbacks for pre & post run
      this.onPreRun.push(function(apiParams) {
        apiParams.debug = true;
      });

      this.onPostRun.push(function(result) {
        ctrl.debug = _.extend(_.pick(ctrl.debug, 'apiParams'), result.debug);
      });

      $scope.sortableColumnOptions = {
        axis: 'x',
        handle: '.crm-draggable',
        update: function(e, ui) {
          // Don't allow items to be moved to position 0 if locked
          if (!ui.item.sortable.dropindex && ctrl.crmSearchAdmin.groupExists) {
            ui.item.sortable.cancel();
          }
          // Function selectors use `ng-repeat` with `track by $index` so must be refreshed when rearranging the array
          ctrl.crmSearchAdmin.hideFuncitons();
        }
      };

      $scope.fieldsForSelect = function() {
        return {results: ctrl.crmSearchAdmin.getAllFields(':label', ['Field', 'Custom', 'Extra', 'Pseudo'], function(key) {
            return _.contains(ctrl.search.api_params.select, key);
          })
        };
      };

      $scope.addColumn = function(col) {
        ctrl.crmSearchAdmin.addParam('select', col);
      };

      $scope.removeColumn = function(index) {
        ctrl.crmSearchAdmin.clearParam('select', index);
      };

    }
  });

})(angular, CRM.$, CRM._);
