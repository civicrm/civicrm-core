(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchActions').controller('crmSearchActionDelete', function($scope, dialogService) {
    var ts = $scope.ts = CRM.ts(),
      model = $scope.model,
      ctrl = $scope.$ctrl = this;

    this.entityTitle = model.ids.length === 1 ? model.entityInfo.title : model.entityInfo.title_plural;

    this.cancel = function() {
      dialogService.cancel('crmSearchAction');
    };

    this.delete = function() {
      $('.ui-dialog-titlebar button').hide();
      ctrl.run = {};
    };

    this.onSuccess = function() {
      CRM.alert(ts('Successfully deleted %1 %2.', {1: model.ids.length, 2: ctrl.entityTitle}), ts('Deleted'), 'success');
      dialogService.close('crmSearchAction');
    };

    this.onError = function() {
      CRM.alert(ts('An error occurred while attempting to delete %1 %2.', {1: model.ids.length, 2: ctrl.entityTitle}), ts('Error'), 'error');
      dialogService.close('crmSearchAction');
    };

  });
})(angular, CRM.$, CRM._);
