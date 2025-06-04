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
    controller: function($scope, $element, $location, $interval, $q, crmApi4, searchDisplayBaseTrait, searchDisplayEditableTrait) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      // Mix in required traits
      const ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayEditableTrait));

      let autoSaveTimer;

      // This display has no search button - results always load immediately if a userJobId is given
      this.loading = true;
      this.unsavedChanges = false;

      this.$onInit = function() {
        this.limit = this.settings.limit || 0;
        // When previewing on the search admin screen, the display will be limited
        this.isPreviewMode = typeof this.search !== 'string';
        this.userJobId = this.isPreviewMode ? null : $location.search().batch;
        // Run search if a userJobId is given. Otherwise the "Start New Batch" button will be shown.
        if (this.userJobId) {
          this.runSearch();
          // Autosave every 10 seconds
          autoSaveTimer = $interval(function() {
            ctrl.saveRows();
          }, 10000);
        }
        else {
          this.newBatchRowCount = 1;
          this.reportLinks = [
            {
              title: ts('View My Import Batches'),
              href: CRM.url('civicrm/imports/my-listing'),
              icon: 'fa-user-tag',
            },
          ];
          if (CRM.checkPerm('administer queues')) {
            this.reportLinks.push({
              title: ts('View All Import Batches'),
              href: CRM.url('civicrm/imports/all-imports'),
              icon: 'fa-list-alt',
            });
          }
        }
        if (this.isPreviewMode) {
          this.results = [{data: {}}];
          this.loading = false;
        }
      };

      this.$onDestroy = function() {
        if (typeof autoSaveTimer !== 'undefined') {
          $interval.cancel(autoSaveTimer);
        }
      };

      // Override function in base class: this sets the current page of results
      this.getResultsPronto = function() {
        if (this.limit) {
          const start = (this.page - 1) * this.limit;
          this.resultsPage = this.results.slice(start, start + this.limit);
        } else {
          this.resultsPage = this.results;
        }
      };

      $scope.$watch('$ctrl.results.length', function() {
        if (ctrl.results) {
          ctrl.rowCount = ctrl.results.length;
          // If no more items on this page, go to the previous page
          if (ctrl.limit && ctrl.page > 1 && Math.ceil(ctrl.results.length / ctrl.limit) < ctrl.page) {
            ctrl.page--;
          }
          ctrl.getResultsPronto();
        }
      });

      $scope.$watch('$ctrl.limit', function() {
        if (ctrl.results) {
          ctrl.page = 1;
          ctrl.getResultsPronto();
        }
      });

      this.createNewBatch = function() {
        this.creatingBatch = true;
        crmApi4('SearchDisplay', 'createBatch', {
          savedSearch: this.search,
          display: this.display,
          rowCount: this.newBatchRowCount,
        }, 0).then(function(userJob) {
          $location.search('batch', userJob.id);
        });
      };

      this.addRows = function(rowCount) {
        for (let i = 0; i < rowCount; i++) {
          let data = this.settings.columns.reduce(function (defaults, col) {
            if ('default' in col) {
              defaults[col.spec.name] = col.default;
            }
            return defaults;
          }, {});
          this.results.push({data: data});
        }
        cancelSave();
      };

      this.deleteRow = function(index) {
        const start = (this.page - 1) * this.limit;
        this.results.splice(start + index, 1);
        cancelSave();
      };

      this.onChangeData = function() {
        ctrl.unsavedChanges = true;
      };

      this.saveRows = function() {
        if (this.saving) {
          return this.saving;
        }
        if (!this.unsavedChanges) {
          return $q.resolve();
        }
        this.unsavedChanges = false;
        const apiName = 'Import_' + this.userJobId;
        this.saving = crmApi4(apiName, 'replace', {
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
        return this.saving;
      };

      this.doImport = function() {
        $element.block();
        this.saveRows().then(function() {
          crmApi4('SearchDisplay', 'importBatch', {
            savedSearch: ctrl.search,
            display: ctrl.display,
            userJobId: ctrl.userJobId,
          }).then(function(result) {
            window.location.href = result[0].url;
          });
        });
      };

      this.copyCol = function(index) {
        const fieldName = this.settings.columns[index].spec.name;
        const value = this.results[0].data[fieldName];
        this.results.forEach((row) => row.data[fieldName] = value);
        ctrl.unsavedChanges = true;
      };

      this.getTally = function(col) {
        if (this.isPreviewMode) {
          return 0;
        }
        const fn = col.tally.fn;
        let tally = 0;
        this.results.forEach(function(row) {
          if (row.data[col.spec.name]) {
            if (fn === 'COUNT') {
              tally++;
            } else {
              const colVal = Number(row.data[col.spec.name]);
              if (!isNaN(colVal)) {
                tally += colVal;
              }
            }
          }
        });
        if (fn === 'AVG' && this.results.length) {
          return tally / this.results.length;
        }
        return tally;
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
