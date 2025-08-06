 (function(angular, $, _) {
  "use strict";

  // Trait shared by any search display controllers which use tasks
  angular.module('crmSearchTasks').factory('searchDisplayTasksTrait', function($rootScope, $window, crmApi4, dialogService) {
    var ts = CRM.ts('org.civicrm.search_kit');

    // TaskManager object is responsible for fetching task metadata for a SearchDispaly
    // and handles the running of tasks.
    function TaskManager(displayCtrl, $element) {
      var mngr = this;
      var fetchedMetadata;
      this.tasks = null;
      this.entityInfo = null;

      this.getMetadata = function() {
        if (!fetchedMetadata) {
          fetchedMetadata = crmApi4({
            entityInfo: ['Entity', 'get', {select: ['name', 'title', 'title_plural', 'primary_key'], where: [['name', '=', mngr.getEntityName()]]}, 0],
            tasks: ['SearchDisplay', 'getSearchTasks', {savedSearch: displayCtrl.search, display: displayCtrl.display}]
          }).then(function(result) {
            mngr.entityInfo = result.entityInfo;
            mngr.tasks = result.tasks;
          }, function(failure) {
            mngr.tasks = [];
            mngr.entityInfo = [];
          });
        }
        return fetchedMetadata;
      };

      this.getEntityName = function() {
        return displayCtrl.apiEntity === 'RelationshipCache' ? 'Relationship' : displayCtrl.apiEntity;
      };
      this.getApiParams = function() {
        return displayCtrl.getApiParams();
      };
      this.getRowCount = function() {
        return displayCtrl.rowCount;
      };
      this.isDisplayReady = function() {
        return !displayCtrl.loading && displayCtrl.results && displayCtrl.results.length;
      };
      this.getTaskInfo = function(taskName) {
        return _.findWhere(mngr.tasks, {name: taskName});
      };

      this.doTask = function(task, ids, isLink) {
        var data = {
          ids: ids,
          entity: mngr.getEntityName(),
          search: displayCtrl.search,
          display: displayCtrl.display,
          displayCtrl: displayCtrl,
          taskManager: mngr,
          entityInfo: mngr.entityInfo,
          isLink: isLink,
          task: _.cloneDeep(task),
        };
        // If task uses a crmPopup form
        if (task.crmPopup) {
          var path = $rootScope.$eval(task.crmPopup.path, data),
            query = task.crmPopup.query && $rootScope.$eval(task.crmPopup.query, data);
          CRM.loadForm(CRM.url(path, query, 'back'), {post: task.crmPopup.data && $rootScope.$eval(task.crmPopup.data, data)})
            .on('crmFormSuccess', (e) => {
                // refreshAfterTask emits its own
                // crmPopupFormSuccess event
                e.stopPropagation();
                mngr.refreshAfterTask();
            });
        }
        else if (task.redirect) {
          var redirectPath = $rootScope.$eval(task.redirect.path, data),
            redirectQuery = task.redirect.query && $rootScope.$eval(task.redirect.query, data) && $rootScope.$eval(task.redirect.data, data);
          $window.open(CRM.url(redirectPath, redirectQuery, 'back'), '_blank');
        }
        // If task uses dialogService
        else {
          var options = CRM.utils.adjustDialogDefaults({
            autoOpen: false,
            dialogClass: 'crm-search-task-dialog',
            title: task.title
          });
          dialogService.open('crmSearchTask', (task.uiDialog && task.uiDialog.templateUrl) || '~/crmSearchTasks/crmSearchTaskApiBatch.html', data, options)
            // Reload results on success, do nothing on cancel
            .then((result) => mngr.refreshAfterTask(result, ids), _.noop);
        }
      };

      this.refreshAfterTask = function(result, ids) {
        displayCtrl.selectedRows = [];
        displayCtrl.allRowsSelected = false;
        if (ids && result.action === 'inlineEdit' && ids.length === 1) {
          displayCtrl.refreshAfterEditing(result, ids[0]);
        }
        else {
          displayCtrl.rowCount = null;
          displayCtrl.getResultsPronto();
          // Trigger all other displays in the same form to update.
          // This display won't update twice because of the debounce in getResultsPronto()
          $element.trigger('crmPopupFormSuccess');
        }
      };
    }

    // Trait properties get mixed into display controller using angular.extend()
    return {

      // Use ajax to select all rows on every page
      selectAllPages: function() {
        var ctrl = this;
        ctrl.loadingAllRows = ctrl.allRowsSelected = true;
        var params = ctrl.getApiParams('id');
        crmApi4('SearchDisplay', 'run', params).then(function(ids) {
          ctrl.loadingAllRows = false;
          ctrl.selectedRows = _.uniq(_.toArray(ids));
        });
      },

      // Select all rows on the current page
      selectPage: function() {
        this.allRowsSelected = (this.rowCount <= this.results.length);
        this.selectedRows = _.uniq(_.pluck(this.results, 'key'));
      },

      // Clear selection
      selectNone: function() {
        this.allRowsSelected = false;
        this.selectedRows = [];
      },

      // Toggle the "select all" checkbox
      toggleAllRows: function() {
        // Deselect all
        if (this.selectedRows && this.selectedRows.length) {
          this.selectNone();
        }
        // Select all
        else if (this.page === 1 && this.rowCount === this.results.length) {
          this.selectPage();
        }
        // If more than one page of results, use ajax to fetch all ids
        else {
          this.selectAllPages();
        }
      },

      // Toggle row selection
      toggleRow: function(row, event) {
        this.selectedRows = this.selectedRows || [];
        var ctrl = this,
          index = ctrl.selectedRows.indexOf(row.key);

        // See if any boxes are checked above/below this one
        function checkRange(allRows, checkboxPosition, dir) {
          for (var row = checkboxPosition; row >= 0 && row <= allRows.length; row += dir) {
            if (ctrl.selectedRows.indexOf(allRows[row]) > -1) {
              return row;
            }
          }
        }

        // Check a bunch of boxes
        function selectRange(allRows, start, end) {
          for (var row = start; row <= end; ++row) {
            ctrl.selectedRows.push(allRows[row]);
          }
        }

        if (index < 0) {
          // Shift-click - select range between clicked checkbox and the nearest selected row
          if (event.shiftKey && ctrl.selectedRows.length) {
            var allRows = _.pluck(ctrl.results, 'key'),
              checkboxPosition = allRows.indexOf(row.key);

            var nearestBefore = checkRange(allRows, checkboxPosition, -1),
              nearestAfter = checkRange(allRows, checkboxPosition, 1);

            // Select range between clicked box and the previous/next checked box
            // In the ambiguous situation where there are checked boxes both above AND below the clicked box,
            // choose the direction of the box which was most recently clicked.
            if (nearestAfter !== undefined && (nearestBefore === undefined || nearestAfter === allRows.indexOf(_.last(ctrl.selectedRows)))) {
              selectRange(allRows, checkboxPosition + 1, nearestAfter - 1);
            } else if (nearestBefore !== undefined && (nearestAfter === undefined || nearestBefore === allRows.indexOf(_.last(ctrl.selectedRows)))) {
              selectRange(allRows, nearestBefore + 1, checkboxPosition -1);
            }
          }
          ctrl.selectedRows = _.uniq(ctrl.selectedRows.concat([row.key]));
          ctrl.allRowsSelected = (ctrl.rowCount === ctrl.selectedRows.length);
        } else {
          ctrl.allRowsSelected = false;
          ctrl.selectedRows.splice(index, 1);
        }
      },

      // @return bool
      isRowSelected: function(row) {
        return this.allRowsSelected || _.includes(this.selectedRows, row.key);
      },

      isPageSelected: function() {
        return (this.allRowsSelected && this.rowCount === this.results.length) ||
          (!this.allRowsSelected && this.selectedRows && this.selectedRows.length === this.results.length);
      },

      // If link is to a task rather than an ordinary href, run the task
      onClickLink: function(link, id, event) {
        if (link.task) {
          const mngr = this.taskManager;
          event.preventDefault();
          mngr.getMetadata().then(function() {
            mngr.doTask(_.extend({title: link.title}, mngr.getTaskInfo(link.task)), [id], true);
          });
        }
      },

      // onInitialize callback
      onInitialize: [function($scope, $element) {
        // Instantiate task manager object
        if (!this.taskManager) {
          this.taskManager = new TaskManager(this, $element);
        }
      }],

      // onChangeFilters callback
      onChangeFilters: [function() {
        // Reset selection when filters are changed
        this.selectedRows = [];
        this.allRowsSelected = false;
      }],

      // onPostRun callback (gets merged with others via angular.extend)
      onPostRun: [function(apiResults, status, editedRow) {
        if (editedRow && status === 'success' && this.selectedRows) {
          // If edited row disappears (because edits cause it to not meet search criteria), deselect it
          var index = this.selectedRows.indexOf(editedRow.key);
          if (index > -1 && !_.findWhere(apiResults.run, {key: editedRow.key})) {
            this.selectedRows.splice(index, 1);
          }
        }
        else if (status === 'success' && !editedRow && apiResults.run && apiResults.run[0]) {
          const mngr = this.taskManager;
          // If tasks are shown as buttons, they need to be loaded right away
          if (this.settings.actions_display_mode === 'buttons') {
            mngr.getMetadata();
            return;
          }
          // If results contain a link to a task, prefetch task info to prevent latency when clicking the link
          _.each(apiResults.run[0].columns, function(column) {
            if ((column.link && column.link.task) || _.find(column.links || [], 'task')) {
              mngr.getMetadata();
            }
          });
        }
      }]

    };
  });

})(angular, CRM.$, CRM._);
