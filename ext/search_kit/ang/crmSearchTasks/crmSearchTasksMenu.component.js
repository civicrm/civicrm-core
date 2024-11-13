(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchTasksMenu', {
    bindings: {
      taskManager: '<',
      displayMode: '<',
      ids: '<'
    },
    template: '<div class="btn-group" ng-include="\'~/crmSearchTasks/crmSearchTasks-\'+$ctrl.displayMode+\'.html\'"></div>',
    controller: function($scope) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        // When a row is selected for bulk actions, load the actions menu
        var unwatchIDs = $scope.$watch('$ctrl.ids.length', function (idsLength) {
          if (idsLength) {
            unwatchIDs();
            ctrl.taskManager.getMetadata();
          }
        });
      };

      this.isActionAllowed = function(action) {
        return $scope.$eval('' + ctrl.ids.length + action.number);
      };

      this.getActionTitle = function(action) {
        if (ctrl.isActionAllowed(action)) {
          return ctrl.ids.length ?
            ts('Perform action on %1 %2', {1: ctrl.ids.length, 2: ctrl.taskManager.entityInfo[ctrl.ids.length === 1 ? 'title' : 'title_plural']}) :
            ts('Perform action on all %1', {1: ctrl.taskManager.entityInfo.title_plural});
        }
        return ts('Selected number must be %1', {1: action.number.replace('===', '')});
      };

      this.doAction = function(action) {
        if (!ctrl.isActionAllowed(action)) {
          return;
        }
        ctrl.taskManager.doTask(action, ctrl.ids);
      };
    }
  });

})(angular, CRM.$, CRM._);
