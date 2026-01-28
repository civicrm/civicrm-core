(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchTasks').controller('crmSearchTaskSoftCredit', function($scope, crmApi4, searchTaskBaseTrait) {
    const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
    const ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    ctrl.values = ctrl.task.values || {};
    ctrl.softCreditTypes = [];

    crmApi4('OptionValue', 'get', {
      select: ['value','label','is_default'],
      where: [['option_group_id:name','=','soft_credit_type'],['is_active','=',true]]
    }).then(function(result) {
      angular.copy(
        result.map(o => ({id: o.value, text: o.label})),
        ctrl.softCreditTypes
      );
      const defaultOpt = result.find(o => o.is_default);
      if (defaultOpt) {
        ctrl.values.soft_credit_type = defaultOpt.value;
      }
    });

    ctrl.submit = function() {
      var params = {};
      params.defaults = {
        contact_id: ctrl.values.contact_id,
        soft_credit_type_id: ctrl.values.soft_credit_type
      };
      ctrl.start(params);
    };

    this.onSuccess = function(result) {
      // ContributionSoft API does not return countMatched even if a soft credit already exists for the same contact, type and amount.
      let msg = result.batchCount === 1 ? ts('1 soft credit added') : ts('%1 soft credits added.', {1: result.batchCount});
      CRM.alert(msg, ts('Saved'), 'success');
      this.close(result);
    };

  });
})(angular, CRM.$, CRM._);
