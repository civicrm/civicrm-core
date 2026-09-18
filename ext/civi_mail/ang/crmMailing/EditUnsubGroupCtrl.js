(function(angular, $) {

  angular.module('crmMailing').controller('EditUnsubGroupCtrl', function EditUnsubGroupCtrl($scope, crmMailingLoader) {
    // CRM.crmMailing.groupNames is a global constant - since it doesn't change, we can digest & cache.
    var mandatoryIds = [];

    $scope.isUnsubGroupRequired = function isUnsubGroupRequired(mailing) {
      crmMailingLoader.getGroupNames(mailing);

      if (CRM.crmMailing.groupNames.length) {
        CRM.crmMailing.groupNames.forEach((grp) => {
          if (grp.is_hidden == "1") {
            mandatoryIds.push(parseInt(grp.id));
          }
        });
        return mandatoryIds.some((id) => mailing.recipients.groups.include.includes(id));
      }
    };
  });

})(angular, CRM.$);
