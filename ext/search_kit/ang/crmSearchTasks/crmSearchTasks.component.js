(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchTasks', {
    bindings: {
      entity: '<',
      refresh: '&',
      search: '<',
      display: '<',
      displayController: '<',
      ids: '<'
    },
    templateUrl: '~/crmSearchTasks/crmSearchTasks.html',
    controller: function($scope, crmApi4, dialogService) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        initialized = false,
        unwatchIDs = $scope.$watch('$ctrl.ids.length', watchIDs);

      function watchIDs() {
        if (ctrl.ids && ctrl.ids.length) {
          unwatchIDs();
          ctrl.getTasks();
        }
      }

      this.getTasks = function() {
        if (initialized) {
          return;
        }
        initialized = true;
        crmApi4({
          entityInfo: ['Entity', 'get', {select: ['name', 'title', 'title_plural'], where: [['name', '=', ctrl.entity]]}, 0],
          tasks: ['SearchDisplay', 'getSearchTasks', {entity: ctrl.entity}]
        }).then(function(result) {
          ctrl.entityInfo = result.entityInfo;
          ctrl.tasks = result.tasks;
        });
      };

      this.isActionAllowed = function(action) {
        return $scope.$eval('' + ctrl.ids.length + action.number);
      };

      this.getActionTitle = function(action) {
        if (ctrl.isActionAllowed(action)) {
          return ctrl.ids.length ?
            ts('Perform action on %1 %2', {1: ctrl.ids.length, 2: ctrl.entityInfo[ctrl.ids.length === 1 ? 'title' : 'title_plural']}) :
            ts('Perform action on all %1', {1: ctrl.entityInfo.title_plural});
        }
        return ts('Selected number must be %1', {1: action.number.replace('===', '')});
      };

      this.doAction = function(action) {
        if (!ctrl.isActionAllowed(action)) {
          return;
        }
        var data = {
          ids: ctrl.ids,
          entity: ctrl.entity,
          search: ctrl.search,
          display: ctrl.display,
          displayController: ctrl.displayController,
          entityInfo: ctrl.entityInfo
        };
        // If action uses a crmPopup form
        if (action.crmPopup) {
          var path = $scope.$eval(action.crmPopup.path, data),
            query = action.crmPopup.query && $scope.$eval(action.crmPopup.query, data);
          CRM.loadForm(CRM.url(path, query), {post: action.crmPopup.data && $scope.$eval(action.crmPopup.data, data)})
            .on('crmFormSuccess', ctrl.refresh);
        }
        // If action uses dialogService
        else if (action.uiDialog) {
          var options = CRM.utils.adjustDialogDefaults({
            autoOpen: false,
            dialogClass: 'crm-search-task-dialog',
            title: action.title
          });
          dialogService.open('crmSearchTask', action.uiDialog.templateUrl, data, options)
            // Reload results on success, do nothing on cancel
            .then(ctrl.refresh, _.noop);
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
