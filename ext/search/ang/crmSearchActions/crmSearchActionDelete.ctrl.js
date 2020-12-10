(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchActions').controller('crmSearchActionDelete', function($scope, crmApi4, dialogService) {
    var ts = $scope.ts = CRM.ts(),
      model = $scope.model,
      ctrl = $scope.$ctrl = this;

    this.entityTitle = model.ids.length === 1 ? model.entityInfo.title : model.entityInfo.title_plural;

    this.cancel = function() {
      dialogService.cancel('crmSearchAction');
    };

    this.delete = function() {
      crmApi4(model.entity, 'Delete', {
        where: [['id', 'IN', model.ids]],
      }).then(function() {
        dialogService.close('crmSearchAction');
      });
    };

  });
})(angular, CRM.$, CRM._);
