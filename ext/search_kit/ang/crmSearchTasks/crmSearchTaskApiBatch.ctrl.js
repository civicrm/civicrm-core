(function(angular, $, _) {
  "use strict";

  // Generic controller for running an ApiBatch task
  angular.module('crmSearchTasks').controller('crmSearchTaskApiBatch', function($scope, searchTaskBaseTrait) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.entityTitle = this.getEntityTitle();

    // If no confirmation message, skip straight to processing
    if (!ctrl.apiBatch.confirmMsg) {
      ctrl.start(ctrl.apiBatch.params);
    }

    this.onSuccess = function(result) {
      var entityTitle = this.getEntityTitle(result.batchCount);
      CRM.alert(ts(ctrl.apiBatch.successMsg, {1: result.batchCount, 2: entityTitle}), ts('%1 Complete', {1: ctrl.taskTitle}), 'success');
      this.close();
    };

    this.onError = function() {
      CRM.alert(ts(ctrl.apiBatch.errorMsg, {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
