(function(angular, $) {

  angular.module('crmMailing').controller('ViewRecipCtrl', function ViewRecipCtrl($scope, crmMailingLoader) {

    $scope.getIncludesAsString = function(mailing) {
      var first = true;
      var names = '';
      crmMailingLoader.getGroupNames(mailing);
      crmMailingLoader.getCiviMails(mailing);
      mailing.recipients.groups.include.forEach((id) => {
        var group = CRM.crmMailing.groupNames.filter((g) => g.id === parseInt(id));
        if (group.length) {
          if (!first) {
            names = names + ', ';
          }
          names = names + group[0].title;
          first = false;
        }
      });
      mailing.recipients.mailings.include.forEach((id) => {
        var oldMailing = CRM.crmMailing.civiMails.filter((m) => m.id === parseInt(id));
        if (oldMailing.length) {
          if (!first) {
            names = names + ', ';
          }
          names = names + oldMailing[0].name;
          first = false;
        }
      });
      return names;
    };
    $scope.getExcludesAsString = function(mailing) {
      var first = true;
      var names = '';
      mailing.recipients.groups.exclude.forEach((id) => {
        var group = CRM.crmMailing.groupNames.filter((g) => g.id === parseInt(id));
        if (group.length) {
          if (!first) {
            names = names + ', ';
          }
          names = names + group[0].title;
          first = false;
        }
      });
      mailing.recipients.mailings.exclude.forEach((id) => {
        var oldMailing = CRM.crmMailing.civiMails.filter((m) => m.id === parseInt(id));
        if (oldMailing.length) {
          if (!first) {
            names = names + ', ';
          }
          names = names + oldMailing[0].name;
          first = false;
        }
      });
      return names;
    };
  });

})(angular, CRM.$);
