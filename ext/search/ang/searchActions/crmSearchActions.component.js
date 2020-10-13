(function(angular, $, _) {
  "use strict";

  angular.module('searchActions').component('crmSearchActions', {
    bindings: {
      entity: '<',
      refresh: '&',
      ids: '<'
    },
    templateUrl: '~/searchActions/crmSearchActions.html',
    controller: function($scope, crmApi4, dialogService, searchMeta) {
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
        var entityTitle = searchMeta.getEntity(ctrl.entity).titlePlural;
        crmApi4(ctrl.entity, 'getActions', {
          where: [['name', 'IN', ['update', 'delete']]],
        }, ['name']).then(function(allowed) {
          _.each(allowed, function(action) {
            CRM.searchActions.tasks[action].entities.push(ctrl.entity);
          });
          var actions = _.transform(_.cloneDeep(CRM.searchActions.tasks), function(actions, action) {
            if (_.includes(action.entities, ctrl.entity)) {
              action.title = action.title.replace('%1', entityTitle);
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
          entity: ctrl.entity
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
