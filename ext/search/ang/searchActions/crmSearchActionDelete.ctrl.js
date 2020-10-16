(function(angular, $, _) {
  "use strict";

  angular.module('searchActions').controller('crmSearchActionDelete', function($scope, crmApi4, dialogService, searchMeta) {
    var ts = $scope.ts = CRM.ts(),
      model = $scope.model,
      ctrl = $scope.$ctrl = this;

    this.entity = searchMeta.getEntity(model.entity);
    this.entityTitle = model.ids.length === 1 ? this.entity.title : this.entity.titlePlural;

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
