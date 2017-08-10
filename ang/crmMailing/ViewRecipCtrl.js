(function(angular, $, _) {

  angular.module('crmMailing').controller('ViewRecipCtrl', function ViewRecipCtrl($scope) {
    $scope.getIncludesAsString = function(mailing) {
      var first = true;
      var names = '';
      _.each(mailing.recipients.groups.include, function(id) {
        var group = _.where(CRM.crmMailing.groupNames, {id: '' + id});
        if (group.length) {
          if (!first) {
            names = names + ', ';
          }
          names = names + group[0].title;
          first = false;
        }
      });
      _.each(mailing.recipients.mailings.include, function(id) {
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: '' + id});
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
      _.each(mailing.recipients.groups.exclude, function(id) {
        var group = _.where(CRM.crmMailing.groupNames, {id: '' + id});
        if (group.length) {
          if (!first) {
            names = names + ', ';
          }
          names = names + group[0].title;
          first = false;
        }
      });
      _.each(mailing.recipients.mailings.exclude, function(id) {
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: '' + id});
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

})(angular, CRM.$, CRM._);
