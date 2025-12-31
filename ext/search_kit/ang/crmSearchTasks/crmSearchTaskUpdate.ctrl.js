(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskUpdate', function ($scope, $timeout, crmApi4, searchTaskBaseTrait, searchTaskFieldsTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    // Combine this controller with model properties (ids, entity, entityInfo) and base traits
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait, searchTaskFieldsTrait);

    this.entityTitle = this.getEntityTitle();

    this.loadFieldsAndValues(this.task, this.entity);

    this.save = function() {
      ctrl.start({
        values: _.zipObject(ctrl.values)
      });
    };

    this.onSuccess = function() {
      CRM.alert(ts('Successfully updated %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Saved'), 'success');
      this.close();
    };

    this.onError = function(error) {
      CRM.alert(ts('An error occurred while attempting to update %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
