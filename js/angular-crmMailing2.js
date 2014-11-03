(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailing2/' + relPath;
  };

  var crmMailing2 = angular.module('crmMailing2', ['ngRoute', 'ui.utils', 'crmUi', 'dialogService']); // TODO ngSanitize, unsavedChanges

  /**
   * Initialize a new mailing
   * TODO Move to separate file or service
   */
  var createMailing = function () {
    var pickDefaultMailComponent = function(type) {
      var mcs = _.where(CRM.crmMailing.headerfooterList, {
        component_type:type,
        is_default: "1"
      });
      return (mcs.length >= 1) ? mcs[0].id : null;
    };

    return {
      name: "",
      campaign_id: null,
      from: _.where(CRM.crmMailing.fromAddress, {is_default: "1"})[0].label,
      replyto_email: "",
      subject: "",
      groups: {include: [], exclude: [4]}, // fixme
      mailings: {include: [], exclude: []},
      body_html: "",
      body_text: "",
      footer_id: null, // pickDefaultMailComponent('Footer'),
      header_id: null, // pickDefaultMailComponent('Header'),
      visibility: "Public Pages",
      url_tracking: "1",
      dedupe_email: "1",
      forward_replies: "0",
      auto_responder: "0",
      open_tracking: "1",
      override_verp: "1",
      optout_id: pickDefaultMailComponent('OptOut'),
      reply_id: pickDefaultMailComponent('Reply'),
      resubscribe_id: pickDefaultMailComponent('Resubscribe'),
      unsubscribe_id: pickDefaultMailComponent('Unsubscribe')
    };
  };

  var getMailing = function ($route, crmApi) {
    return ($route.current.params.id == 'new') ? createMailing() : crmApi('Mailing', 'getsingle', {id: $route.current.params.id});
  };

  crmMailing2.config(['$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/mailing2', {
        template: '<div></div>',
        controller: 'ListMailingsCtrl'
      });
      $routeProvider.when('/mailing2/:id', {
        templateUrl: partialUrl('edit.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: getMailing
        }
      });
      $routeProvider.when('/mailing2/:id/unified', {
        templateUrl: partialUrl('edit-unified.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: getMailing
        }
      });
      $routeProvider.when('/mailing2/:id/unified2', {
        templateUrl: partialUrl('edit-unified2.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: getMailing
        }
      });
      $routeProvider.when('/mailing2/:id/wizard', {
        templateUrl: partialUrl('edit-wizard.html'),
        controller: 'EditMailingCtrl',
        resolve: {
          selectedMail: getMailing
        }
      });
    }
  ]);

  crmMailing2.controller('ListMailingsCtrl', function ($scope) {
    // We haven't implemented this in Angular, but some users may get clever
    // about typing URLs, so we'll provide a redirect.
    window.location = CRM.url('civicrm/mailing/browse/unscheduled', {
      reset: 1,
      scheduled: 'false'
    });
  });

  crmMailing2.controller('EditMailingCtrl', function ($scope, selectedMail) {
    $scope.mailing = selectedMail;
    $scope.crmMailingConst = CRM.crmMailing;

    $scope.partialUrl = partialUrl;
    $scope.ts = CRM.ts('CiviMail');

    $scope.send = function() {
      CRM.alert('Send!');
    };
    $scope.save = function() {
      CRM.alert('Save!');
    };
    $scope.cancel = function() {
      CRM.alert('Cancel!');
    };
    $scope.leave = function() {
      window.location = CRM.url('civicrm/mailing/browse/unscheduled', {
        reset: 1,
        scheduled: 'false'
      });
    };
  });

})(angular, CRM.$, CRM._);
