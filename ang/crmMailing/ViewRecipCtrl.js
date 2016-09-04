(function(angular, $, _) {

  angular.module('crmMailing').controller('ViewRecipCtrl', function ViewRecipCtrl($scope) {
    $scope.getIncludesAsString = function(mailing) {
      var first = true;
      var names = '';
      _.each(mailing.recipients.groups.include, function(id) {
        if (!first) {
          names = names + ', ';
        }
        var group = _.where(CRM.crmMailing.groupNames, {id: '' + id});
        names = names + group[0].title;
        first = false;
      });
      _.each(mailing.recipients.mailings.include, function(id) {
        if (!first) {
          names = names + ', ';
        }
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: '' + id});
        names = names + oldMailing[0].name;
        first = false;
      });
      return names;
    };
    $scope.getExcludesAsString = function(mailing) {
      var first = true;
      var names = '';
      _.each(mailing.recipients.groups.exclude, function(id) {
        if (!first) {
          names = names + ', ';
        }
        var group = _.where(CRM.crmMailing.groupNames, {id: '' + id});
        names = names + group[0].title;
        first = false;
      });
      _.each(mailing.recipients.mailings.exclude, function(id) {
        if (!first) {
          names = names + ', ';
        }
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: '' + id});
        names = names + oldMailing[0].name;
        first = false;
      });
      return names;
    };
  });

})(angular, CRM.$, CRM._);
