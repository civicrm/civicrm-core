(function(angular, $, _) {
  "use strict";

  angular.module('afSearchTasks').controller('afformSubmissionProcessTask', function ($scope, $timeout, crmApi4, searchTaskBaseTrait) {
    var ts = $scope.ts = CRM.ts('org.civicrm.afform'),
      // Combine this controller with model properties (ids, entity, entityInfo) and searchTaskBaseTrait
      ctrl = angular.extend(this, $scope.model, searchTaskBaseTrait);

    this.entityTitle = this.getEntityTitle();
    this.afformName = '';

    this.getAfformName = function(id) {
      crmApi4('AfformSubmission', 'get', {
        select: ["afform_name"],
        where: [["id", "=", id]],
      }).then(function(afformSubmissions) {
        ctrl.afformName = afformSubmissions[0].afform_name;
      }, function(error) {
        ctrl.onError();
      });
    };

    this.processData = function() {
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
    };

    this.save = function() {
      // get the afform name
      ctrl.getAfformName(ctrl.ids[0]);

      $timeout(function() {
        ctrl.processData();
      },500);

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
