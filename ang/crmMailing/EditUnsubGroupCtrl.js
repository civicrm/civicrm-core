(function(angular, $, _) {

  angular.module('crmMailing').controller('EditUnsubGroupCtrl', function EditUnsubGroupCtrl($scope) {
    // CRM.crmMailing.groupNames is a global constant - since it doesn't change, we can digest & cache.
    var mandatoryIds = [];
    _.each(CRM.crmMailing.groupNames, function(grp) {
      if (grp.is_hidden == "1") {
        mandatoryIds.push(parseInt(grp.id));
      }
    });

    $scope.isUnsubGroupRequired = function isUnsubGroupRequired(mailing) {
      return _.intersection(mandatoryIds, mailing.recipients.groups.include).length > 0;
    };
  });

})(angular, CRM.$, CRM._);
