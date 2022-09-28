(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').component('crmSearchTasks', {
    bindings: {
      entity: '<',
      refresh: '&',
      ids: '<'
    },
    templateUrl: '~/crmSearchTasks/crmSearchTasks.html',
    controller: function($scope, crmApi4, dialogService) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this,
        initialized = false,
        unwatchIDs = $scope.$watch('$ctrl.ids.length', watchIDs);

      function watchIDs() {
        if (ctrl.ids && ctrl.ids.length && !initialized) {
          unwatchIDs();
          initialized = true;
          initialize();
        }
      }

      function initialize() {
        crmApi4({
          entityInfo: ['Entity', 'get', {select: ['name', 'title', 'title_plural'], where: [['name', '=', ctrl.entity]]}, 0],
          tasks: ['SearchDisplay', 'getSearchTasks', {entity: ctrl.entity}]
        }).then(function(result) {
          ctrl.entityInfo = result.entityInfo;
          ctrl.tasks = result.tasks;
        });
      }

      this.isActionAllowed = function(action) {
        return !action.number || $scope.eval('' + $ctrl.ids.length + action.number);
      };

      this.doAction = function(action) {
        if (!ctrl.isActionAllowed(action) || !ctrl.ids.length) {
          return;
        }
        var data = {
          ids: ctrl.ids,
          entity: ctrl.entity,
          entityInfo: ctrl.entityInfo
        };
        // If action uses a crmPopup form
        if (action.crmPopup) {
          var path = $scope.$eval(action.crmPopup.path, data),
            query = action.crmPopup.query && $scope.$eval(action.crmPopup.query, data);
          CRM.loadForm(CRM.url(path, query))
            .on('crmFormSuccess', ctrl.refresh);
        }
        // If action uses dialogService
        else if (action.uiDialog) {
          var options = CRM.utils.adjustDialogDefaults({
            autoOpen: false,
            title: action.title
          });
          dialogService.open('crmSearchTask', action.uiDialog.templateUrl, data, options)
            .then(ctrl.refresh);
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
