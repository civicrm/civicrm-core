(function(angular, $, _) {
  "use strict";

  angular.module('search').component('crmSearchActions', {
    bindings: {
      entity: '<',
      ids: '<'
    },
    require: {
      search: '^crmSearch'
    },
    templateUrl: '~/search/crmSearchActions.html',
    controller: function($scope, crmApi4, dialogService, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        entityTitle = searchMeta.getEntity(this.entity).title,
        ctrl = this;

      this.init = function() {
        if (!ctrl.actions) {
          var actions = _.transform(_.cloneDeep(CRM.vars.search.actions), function (actions, action) {
            if (_.includes(action.entities, ctrl.entity)) {
              action.title = action.title.replace('%1', entityTitle);
              actions.push(action);
            }
          }, []);
          ctrl.actions = _.sortBy(actions, 'title');
        }
      };

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
            .on('crmFormSuccess', ctrl.search.refreshPage);
        }
        // If action uses dialogService
        else if (action.uiDialog) {
          var options = CRM.utils.adjustDialogDefaults({
            autoOpen: false,
            title: action.title
          });
          dialogService.open('crmSearchAction', action.uiDialog.templateUrl, data, options)
            .then(ctrl.search.refreshPage);
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
