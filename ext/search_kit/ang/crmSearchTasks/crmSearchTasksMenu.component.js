(function(angular, $) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchTasksMenu', {
    bindings: {
      taskManager: '<',
      displayMode: '<',
      ids: '<'
    },
    template: '<div class="btn-group" ng-include="\'~/crmSearchTasks/crmSearchTasks-\'+$ctrl.displayMode+\'.html\'"></div>',
    controller: function($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      function updateTaskGroups(tasks) {
        if (!tasks) {
          ctrl.taskGroups = null;
          ctrl.menuTasks = null;
          return;
        }
        const menuTasks = tasks.filter((task) => task.inMenu !== false);
        ctrl.menuTasks = menuTasks;
        const groups = [];
        const groupMap = {};
        menuTasks.forEach((task) => {
          const groupKey = task.join === null ? '__global__' : (task.join || '');
          if (!groupMap[groupKey]) {
            groupMap[groupKey] = {
              key: groupKey,
              label: task.entityLabel || '',
              tasks: [],
            };
            groups.push(groupMap[groupKey]);
          }
          groupMap[groupKey].tasks.push(task);
        });
        ctrl.taskGroups = groups;
      }

      this.$onInit = function() {
        $scope.$watch('$ctrl.taskManager.tasks', updateTaskGroups);
        if (ctrl.displayMode === 'buttons') {
          ctrl.taskManager.getMetadata();
        }
        // When a row is selected for bulk actions, load the actions menu
        let unwatchIDs = $scope.$watch('$ctrl.ids.length', function (idsLength) {
          if (idsLength) {
            unwatchIDs();
            ctrl.taskManager.getMetadata();
          }
        });
      };

      this.getTaskIds = function(action) {
        return ctrl.taskManager.getIdsForTask(action, ctrl.ids);
      };

      this.isActionAllowed = function(action) {
        return $scope.$eval('' + ctrl.getTaskIds(action).length + action.number);
      };

      this.getActionTitle = function(action) {
        const ids = ctrl.getTaskIds(action);
        if (ctrl.isActionAllowed(action)) {
          const entityInfo = ctrl.taskManager.getEntityInfo(action.entity);
          return ids.length ?
            ts('Perform action on %1 %2', {1: ids.length, 2: entityInfo[ids.length === 1 ? 'title' : 'title_plural']}) :
            ts('Perform action on all %1', {1: entityInfo.title_plural});
        }
        return ts('Selected number must be %1', {1: action.number.replace('===', '')});
      };

      this.doAction = function(action) {
        if (!ctrl.isActionAllowed(action)) {
          return;
        }
        ctrl.taskManager.doTask(action, ctrl.getTaskIds(action));
      };
    }
  });

})(angular, CRM.$);
