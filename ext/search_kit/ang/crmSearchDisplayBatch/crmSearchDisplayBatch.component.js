(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayBatch').component('crmSearchDisplayBatch', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      apiParams: '<',
      settings: '<',
      filters: '<',
      totalCount: '=?'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmSearchDisplayBatch/crmSearchDisplayBatch.html',
    controller: function($scope, $element, $location, $interval, crmApi4, searchDisplayBaseTrait, searchDisplayEditableTrait) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      // Mix in required traits
      const ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayEditableTrait));

      let autoSaveTimer;

      // This display has no search button - results always load immediately if a userJobId is given
      this.loading = true;
      this.unsavedChanges = false;

      this.$onInit = function() {
        this.userJobId = $location.search().batch;
        // Run search if a userJobId is given. Otherwise the "Start New Batch" button will be shown.
        if (this.userJobId) {
          // Strip pseudoconstant suffixes from column keys
          this.settings.columns.forEach((col) => col.key = col.key.split(':')[0]);
          this.runSearch();
          // Autosave every 10 seconds
          autoSaveTimer = $interval(function() {
            ctrl.saveRows();
          }, 10000);
        }
      };

      this.$onDestroy = function() {
        if (typeof autoSaveTimer !== 'undefined') {
          $interval.cancel(autoSaveTimer);
        }
      };

      this.createNewBatch = function() {
        this.creatingBatch = true;
        crmApi4('SearchDisplay', 'createBatch', {
          savedSearch: this.search,
          display: this.display,
        }, 0).then(function(userJob) {
          $location.search('batch', userJob.id);
        });
      };

      this.addRows = function(rowCount) {
        for (let i = 0; i < rowCount; i++) {
          this.results.push({data: {}});
        }
        cancelSave();
      };

      this.deleteRow = function(index) {
        this.results.splice(index, 1);
        cancelSave();
      };

      this.onChangeData = function() {
        ctrl.unsavedChanges = true;
      };

      this.saveRows = function() {
        if (this.saving || !this.unsavedChanges) {
          return;
        }
        this.saving = true;
        this.unsavedChanges = false;
        const apiName = 'Import_' + this.userJobId;
        crmApi4(apiName, 'replace', {
          // The api requires this clause, but we actually want every row in the table
          where: [['_id', '>', 0]],
          records: this.results.map((row) => row.data),
        }).then(function(savedRows) {
          ctrl.saving = false;
          if (ctrl.cancelSave) {
            ctrl.cancelSave = false;
            return;
          }
          savedRows.forEach(function(row, index) {
            ctrl.results[index].data._id = row._id;
          });
        }, function(error) {
          ctrl.saving = false;
          ctrl.unsavedChanges = true;
        });
      };

      this.copyCol = function(index) {
        const key = this.settings.columns[index].key;
        const value = this.results[0].data[key];
        this.results.forEach((row) => row.data[key] = value);
        ctrl.unsavedChanges = true;
      };

      // When inserting/deleting rows the ids will shift so cancel pending save & re-queue it
      function cancelSave() {
        if (ctrl.saving) {
          ctrl.cancelSave = true;
        }
        ctrl.unsavedChanges = true;
      }

      // Override base method: add userJobId
      const _getApiParams = this.getApiParams;
      this.getApiParams = function(mode) {
        const apiParams = _getApiParams.call(this, mode);
        apiParams.userJobId = this.userJobId;
        return apiParams;
      };

      // Override name of api action
      this.onPreRun.push(function(apiCalls) {
        apiCalls.run[1] = 'runBatch';
      });

    }
  });

})(angular, CRM.$, CRM._);
