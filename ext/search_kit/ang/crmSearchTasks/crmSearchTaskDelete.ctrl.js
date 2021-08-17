(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskDelete', function($scope, searchTaskBaseTrait) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.entityTitle = this.getEntityTitle();

    this.onSuccess = function() {
      CRM.alert(ts('Successfully deleted %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Deleted'), 'success');
      this.close();
    };

    this.onError = function() {
      CRM.alert(ts('An error occurred while attempting to delete %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
