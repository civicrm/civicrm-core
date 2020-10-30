(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchActions').component('crmSearchActions', {
    bindings: {
      entity: '<',
      refresh: '&',
      ids: '<'
    },
    templateUrl: '~/crmSearchActions/crmSearchActions.html',
    controller: function($scope, crmApi4, dialogService) {
      var ts = $scope.ts = CRM.ts(),
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
          allowed: [ctrl.entity, 'getActions', {where: [['name', 'IN', ['update', 'delete']]]}, ['name']]
        }).then(function(result) {
          ctrl.entityInfo = result.entityInfo;
          _.each(result.allowed, function(action) {
            CRM.crmSearchActions.tasks[action].entities.push(ctrl.entity);
          });
          var actions = _.transform(_.cloneDeep(CRM.crmSearchActions.tasks), function(actions, action) {
            if (_.includes(action.entities, ctrl.entity)) {
              action.title = action.title.replace('%1', ctrl.entityInfo.title_plural);
              actions.push(action);
            }
          }, []);
          ctrl.actions = _.sortBy(actions, 'title');
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
          dialogService.open('crmSearchAction', action.uiDialog.templateUrl, data, options)
            .then(ctrl.refresh);
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
