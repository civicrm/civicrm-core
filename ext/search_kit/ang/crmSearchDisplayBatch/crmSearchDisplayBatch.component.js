(function(angular, $, _) {
  "use strict";

  // Ensures each display gets a unique form name
  let displayInstance = 0;

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
      afFieldset: '?^^afFieldset',
      formCtrl: '?^form',
    },
    templateUrl: '~/crmSearchDisplayBatch/crmSearchDisplayBatch.html',
    controller: function($scope, $element, $location, $interval, $q, crmApi4, searchDisplayBaseTrait, searchDisplayEditableTrait) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      // Mix in required traits
      const ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait), _.cloneDeep(searchDisplayEditableTrait));

      let autoSaveTimer;
      let errorNotification;

      // This display has no search button - results always load immediately if a userJobId is given
      this.loading = true;
      // Array of rows with unsaved changes
      this.unsaved = [];
      this.formName = 'searchDisplayBatch' + displayInstance++;

      this.$onInit = function() {
        this.limit = this.settings.limit || 0;
        if (errorNotification && errorNotification.close) {
          errorNotification.close();
        }
        // When previewing on the search admin screen, the display will be view-only
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
          this.newBatch = {
            rowCount: 1,
            targets: {}
          };
          this.reportLinks = [
            {
              title: ts('View My Import Batches'),
              href: CRM.url('civicrm/imports/my-listing#/?job_type=search_batch_import'),
              icon: 'fa-user-tag',
            },
          ];
          if (CRM.checkPerm('administer queues')) {
            this.reportLinks.push({
              title: ts('View All Import Batches'),
              href: CRM.url('civicrm/imports/all-imports#/?job_type=search_batch_import'),
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
          rowCount: this.newBatch.rowCount,
          targets: this.newBatch.targets,
          label: this.newBatch.label,
        }, 0).then(function(userJob) {
          $location.search('batch', userJob.id);
          // Re-init display to switch modes from creating batch to editing batch
          ctrl.$onInit();
        });
      };

      this.addRows = function(rowCount) {
        this.addingRows = true;
        const newRows = [];
        for (let i = 0; i < rowCount; i++) {
          newRows.push({});
        }
        crmApi4(getApiName(), 'save', {records: newRows, reload: ['*']}).then(function(savedRows) {
          savedRows.forEach(function(row) {
            delete row._entity_id;
            delete row._status;
            delete row._status_message;
            ctrl.results.push({data: row});
          });
          ctrl.addingRows = false;
        });
      };

      this.deleteRow = function(index) {
        const rowNum = (this.page - 1) * this.limit + index;
        const id = this.results[rowNum].data._id;
        // Remove from unsaved array if present
        const unsavedIndex = this.unsaved.findIndex(item => item._id === id);
        if (unsavedIndex !== -1) {
          this.unsaved.splice(unsavedIndex, 1);
        }
        crmApi4(getApiName(), 'delete', {where: [['_id', '=', id]]});
        this.results.splice(rowNum, 1);
      };

      this.onChangeData = function(row) {
        // Add row data to unsaved array if not already present
        if (!this.unsaved.some(item => item._id === row.data._id)) {
          this.unsaved.push(row.data);
        }
        // Calculate any formula fields
        this.settings.columns.forEach(function(col) {
          const editable = ctrl.results.editable[col.key];
          if (editable && editable.input_attrs && editable.input_attrs.formula) {
            let formula = editable.input_attrs.formula;
            const prefix = editable.explicit_join ? (editable.explicit_join + '.') : '';
            formula = formula.replace(/\[/g, '(data["' + prefix);
            formula = formula.replace(/]/g, '"] || 0)');
            row.data[col.spec.name] = $scope.$eval(formula, {data: row.data});
          }
        });
      };

      this.saveRows = function() {
        if (this.saving) {
          return this.saving;
        }
        if (!this.unsaved.length) {
          return $q.resolve();
        }
        const records = this.unsaved;
        // Reset unsaved array to immediately start watching for new changes even before the ajax completes
        this.unsaved = [];
        this.saving = crmApi4(getApiName(), 'save', {
          records: records,
        }).then(function(savedRows) {
          ctrl.saving = false;
        }, function(error) {
          ctrl.saving = false;
        });
        return this.saving;
      };

      this.getFieldName = function(index, key) {
        const rowIndex = ((this.page - 1) * this.limit) + index;
        return 'batch-row-' + rowIndex + '-' + _.snakeCase(key);
      };

      this.isValid = function() {
        return ctrl.formCtrl.$valid;
      };

      this.doImport = function() {
        if (errorNotification && errorNotification.close) {
          errorNotification.close();
        }
        if (!this.isValid()) {
          this.showValidationErrors();
          return;
        }
        const tallyMismatches = getTallyMismatches();
        if (tallyMismatches.length) {
          let markup = '';
          // Run each item in array through _.escape
          tallyMismatches.forEach((item, index, array) => {
            markup += '<p><i class="crm-i fa-warning" role="img" aria-hidden="true"></i> ' + _.escape(item) + '</p>';
          });
          CRM.confirm({
            title: ts('Tally Mismatch'),
            message: markup + '<p>' + _.escape(ts('Run import anyway?')) + '</p>',
            options: {
              no: ts('Cancel'),
              yes: ts('Run Import'),
            },
          }).on('crmConfirm:yes', runImport);
        } else {
          runImport();
        }
      };

      function runImport() {
        $element.block();
        ctrl.saveRows().then(function() {
          crmApi4('SearchDisplay', 'importBatch', {
            savedSearch: ctrl.search,
            display: ctrl.display,
            userJobId: ctrl.userJobId,
          }).then(function(result) {
            window.location.href = result[0].url;
          });
        });
      }

      this.showValidationErrors = function() {
        const formCtrl = this.formCtrl[this.formName];
        let invalidRows = [];
        let messages = [];
        Object.keys(formCtrl).forEach(function(key) {
          if (key.startsWith('batch-row-') && formCtrl[key].$invalid) {
            invalidRows.push(1 + parseInt(key.split('-')[2], 10));
          }
        });
        // Numeric sort
        invalidRows.sort((a, b) => a - b);
        invalidRows = _.uniq(invalidRows, true);

        // Build messages array, grouping consecutive rows
        let start = invalidRows[0];
        let prev = start;
        for (let i = 1; i <= invalidRows.length; i++) {
          const current = invalidRows[i];
          if (current !== prev + 1) {
            // End of a sequence
            if (start === prev) {
              messages.push(_.escape(ts('Row %1', {1: start})));
            } else {
              messages.push(_.escape(ts('Rows %1 to %2', {1: start, 2: prev})));
            }
            start = current;
          }
          prev = current;
        }
        errorNotification = CRM.alert(
          '<ul><li>' + messages.join('</li><li>') + '</li></ul>',
          _.escape(ts('Please complete the following:')),
          'error'
        );
      };

      this.copyCol = function(index) {
        const fieldName = this.settings.columns[index].spec.name;
        const value = this.results[0].data[fieldName];
        const updateValue = {};
        updateValue[fieldName] = value;
        this.results.forEach((row) => row.data[fieldName] = value);
        crmApi4(getApiName(), 'update', {
          where: [['_id', '>', this.results[0].data._id]],
          values: updateValue,
        });
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

      this.getTallyClass = function(col) {
        if (this.isPreviewMode) {
          return '';
        }
        const tallyTarget = this.getTallyTarget(col);
        if (tallyTarget) {
          return this.getTally(col) == tallyTarget ? 'text-success' : 'text-danger';
        }
        return '';
      };

      this.getTallyTarget = function(col) {
        if (col.tally && col.tally.fn === 'SUM' && ctrl.results && ctrl.results.editable[col.key]) {
          return ctrl.results.editable[col.key].target;
        }
      };

      function getTallyMismatches() {
        const tallyMismatches = [];
        ctrl.settings.columns.forEach(function(col) {
          const tallyTarget = ctrl.getTallyTarget(col);
          if (tallyTarget) {
            const tally = ctrl.getTally(col);
            if (tally != tallyTarget) {
              tallyMismatches.push(ts('%1 value %2 does not match expected %3', {1: col.label, 2: tally, 3: tallyTarget}));
            }
          }
        });
        return tallyMismatches;
      }

      function getApiName() {
        return 'Import_' + ctrl.userJobId;
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
