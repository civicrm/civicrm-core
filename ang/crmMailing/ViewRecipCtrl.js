(function(angular, $, _) {

  angular.module('crmMailing').controller('ViewRecipCtrl', function ViewRecipCtrl($scope) {
    var mids = [];
    var gids = [];
    var groupNames = [];
    var mailings = [];
    var civimailings = [];
    var civimails = [];

    function getGroupNames(mailing) {
      if (-1 == mailings.indexOf(mailing.id)) {
        mailings.push(mailing.id);
        _.each(mailing.recipients.groups.include, function(id) {
          if (-1 == gids.indexOf(id)) {
            gids.push(id);
          }
        });
        _.each(mailing.recipients.groups.exclude, function(id) {
          if (-1 == gids.indexOf(id)) {
            gids.push(id);
          }
        });
        _.each(mailing.recipients.groups.base, function(id) {
          if (-1 == gids.indexOf(id)) {
            gids.push(id);
          }
        });
        if (!_.isEmpty(gids)) {
          CRM.api3('Group', 'get', {'id': {"IN": gids}}).then(function(result) {
            _.each(result.values, function(grp) {
              if (_.isEmpty(_.where(groupNames, {id: parseInt(grp.id)}))) {
                groupNames.push({id: parseInt(grp.id), title: grp.title, is_hidden: grp.is_hidden});
              }
            });
            CRM.crmMailing.groupNames = groupNames;
            $scope.$parent.crmMailingConst.groupNames = groupNames;
          });
        }
      }
    }

    function getCiviMails(mailing) {
      if (-1 == civimailings.indexOf(mailing.id)) {
        civimailings.push(mailing.id);
        _.each(mailing.recipients.mailings.include, function(id) {
          if (-1 == mids.indexOf(id)) {
            mids.push(id);
          }
        });
        _.each(mailing.recipients.mailings.exclude, function(id) {
          if (-1 == mids.indexOf(id)) {
            mids.push(id);
          }
        });
        if (!_.isEmpty(mids)) {
          CRM.api3('Mailing', 'get', {'id': {"IN": mids}}).then(function(result) {
            _.each(result.values, function(mail) {
              if (_.isEmpty(_.where(civimails, {id: parseInt(mail.id)}))) {
                civimails.push({id: parseInt(mail.id), name: mail.label});
              }
            });
            CRM.crmMailing.civiMails = civimails;
            $scope.$parent.crmMailingConst.civiMails = civimails;
          });
        }
      }
    }

    $scope.getIncludesAsString = function(mailing) {
      var first = true;
      var names = '';
      if (_.isEmpty(CRM.crmMailing.groupNames)) {
        getGroupNames(mailing);
      }
      if (_.isEmpty(CRM.crmMailing.civiMails)) {
        getCiviMails(mailing);
      }
      _.each(mailing.recipients.groups.include, function(id) {
        var group = _.where(CRM.crmMailing.groupNames, {id: parseInt(id)});
        if (group.length) {
          if (!first) {
            names = names + ', ';
          }
          names = names + group[0].title;
          first = false;
        }
      });
      _.each(mailing.recipients.mailings.include, function(id) {
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: parseInt(id)});
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
        var group = _.where(CRM.crmMailing.groupNames, {id: parseInt(id)});
        if (group.length) {
          if (!first) {
            names = names + ', ';
          }
          names = names + group[0].title;
          first = false;
        }
      });
      _.each(mailing.recipients.mailings.exclude, function(id) {
        var oldMailing = _.where(CRM.crmMailing.civiMails, {id: parseInt(id)});
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
