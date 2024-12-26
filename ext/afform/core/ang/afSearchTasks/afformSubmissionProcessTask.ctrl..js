(function(angular, $, _) {
  "use strict";

  angular.module('afSearchTasks').controller('afformSubmissionProcessTask', function ($scope, $timeout, crmApi4, searchTaskBaseTrait) {
    var ts = $scope.ts = CRM.ts('org.civicrm.afform'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.entityTitle = this.getEntityTitle();
    this.afformName = '';

    this.processData = function (submissionId) {
      crmApi4('AfformSubmission', 'get', {
        select: ["afform_name"],
        where: [["id", "=", submissionId]],
      }).then(function(afformSubmissions) {
        ctrl.afformName = afformSubmissions[0].afform_name;

        _.each(ctrl.ids, function(id) {
          ctrl.start();
          crmApi4('Afform', 'process', {
            submissionId: id,
            name: ctrl.afformName
          }).then(function(result) {
          }, function(failure) {
            ctrl.onError();
          });
        });

        ctrl.onSuccess();

      }, function(error) {
        ctrl.onError();
      });
    };

    this.save = function() {
      // process submission
      ctrl.processData(ctrl.ids[0]);
    };

    this.onSuccess = function() {
      CRM.alert(ts('Successfully processed %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Saved'), 'success');
      this.close();
    };

    this.onError = function() {
      CRM.alert(ts('An error occurred while attempting to process %1 %2.', {1: ctrl.ids.length, 2: ctrl.entityTitle}), ts('Error'), 'error');
      this.cancel();
    };

  });
})(angular, CRM.$, CRM._);
