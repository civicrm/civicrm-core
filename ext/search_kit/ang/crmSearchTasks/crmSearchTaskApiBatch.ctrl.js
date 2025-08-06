(function(angular, $, _) {
  "use strict";

  // Generic controller for running an ApiBatch task
  angular.module('crmSearchTasks').controller('crmSearchTaskApiBatch', function($scope, searchTaskBaseTrait) {
    var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.apiBatch = $scope.apiBatch = this.task.apiBatch;
    this.entityTitle = this.getEntityTitle();

    // If no selectable fields or confirmation message, skip straight to processing
    if (!ctrl.apiBatch.confirmMsg && !ctrl.apiBatch.fields) {
      ctrl.start(ctrl.apiBatch.params);
    }

    if (ctrl.apiBatch.fields) {
      ctrl.apiBatch.params = ctrl.apiBatch.params || {};
      ctrl.apiBatch.params.values = ctrl.apiBatch.params.values || {};
      // Set values from field defaults
      ctrl.apiBatch.fields.forEach((field) => {
        let value = '';
        if ('default_value' in field) {
          value = field.default_value;
        } else if (field.serialize || field.data_type === 'Array') {
          value = [];
        } else if (field.data_type === 'Boolean') {
          value = true;
        } else if (field.options && field.options.length) {
          value = field.options[0].id;
        }
        ctrl.apiBatch.params.values[field.name] = value;
      });
    }

    this.onSuccess = function(result) {
      var entityTitle = this.getEntityTitle(result.batchCount);
      if (result.action === 'inlineEdit') {
        CRM.status(ts('Saved'));
      } else {
        CRM.alert(ts(ctrl.apiBatch.successMsg, {1: result.batchCount, 2: entityTitle}), ts('%1 Complete', {1: ctrl.task.title}), 'success');
      }
      this.close(result);
    };

    this.onError = function() {
      CRM.alert(ts(ctrl.apiBatch.errorMsg, {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
