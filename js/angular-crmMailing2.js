(function (angular, $, _) {
  var partialUrl = function (relPath) {
    return CRM.resourceUrls['civicrm'] + '/partials/crmMailing2/' + relPath;
  };

  var crmMailing2 = angular.module('crmMailing2', ['ngRoute', 'ui.utils', 'crmUi', 'dialogService']); // TODO ngSanitize, unsavedChanges

  // Time to wait before triggering AJAX update to recipients list
  var RECIPIENTS_DEBOUNCE_MS = 100;
  var RECIPIENTS_PREVIEW_LIMIT = 10000;

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
      dedupe_email: "1",
      groups: {include: [2], exclude: [4]}, // fixme
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

  // Controller for the edit-recipients fields (
  // WISHLIST: Move most of this to a (cache-enabled) service
  // Scope members:
  //  - [input] mailing: object
  //  - [output] recipients: array of recipient records
  crmMailing2.controller('EditRecipCtrl', function ($scope, dialogService, crmApi) {
    // TODO load & live update real recipients list
    $scope.recipients = null;
    $scope.getRecipientsEstimate = function () {
      var ts = $scope.ts;
      if ($scope.recipients == null)
        return ts('(Estimating)');
      if ($scope.recipients.length == 0)
        return ts('No recipients');
      if ($scope.recipients.length == 1)
        return ts('~1 recipient');
      if (RECIPIENTS_PREVIEW_LIMIT > 0 && $scope.recipients.length >= RECIPIENTS_PREVIEW_LIMIT)
        return ts('>%1 recipients', {1: RECIPIENTS_PREVIEW_LIMIT});
      return ts('~%1 recipients', {1: $scope.recipients.length});
    };
    // We monitor four fields -- use debounce so that changes across the
    // four fields can settle-down before AJAX.
    var refreshRecipients = _.debounce(function () {
      $scope.$apply(function () {
        $scope.recipients = null;
        // To get list of recipients, we tentatively save the mailing and
        // get the resulting recipients -- then rollback any changes.
        var params = _.extend({}, $scope.mailing, {
          options:  {force_rollback: 1},
          'api.MailingRecipients.get': {
            mailing_id: '$value.id',
            options: {limit: RECIPIENTS_PREVIEW_LIMIT},
            'api.contact.getvalue': {'return': 'display_name'},
            'api.email.getvalue': {'return': 'email'}
          }
        });

        crmApi('Mailing', 'create', params)
                .then(function (recipResult) {
                  $scope.$apply(function () {
                    $scope.recipients = recipResult.values[recipResult.id]['api.MailingRecipients.get'].values;
                  });
                });
      });
    }, RECIPIENTS_DEBOUNCE_MS);
    $scope.$watchCollection("mailing.groups.include", refreshRecipients);
    $scope.$watchCollection("mailing.groups.exclude", refreshRecipients);
    $scope.$watchCollection("mailing.mailings.include", refreshRecipients);
    $scope.$watchCollection("mailing.mailings.exclude", refreshRecipients);

    $scope.previewRecipients = function () {
      var model = {
        recipients: $scope.recipients
      };
      var options = {
        autoOpen: false,
        modal: true,
        title: ts('Preview (%1)', {
          1: $scope.getRecipientsEstimate()
        }),
      };
      dialogService.open('recipDialog', partialUrl('dialog/recipients.html'), model, options)
        .then(
          function (result) {
            // console.log('Closed!');
          },
          function (error) {
            // console.log('Cancelled!');
          }
        );
    };
  });

  // Controller for the "Preview Recipients" dialog
  // Note: Expects $scope.model to be an object with properties:
  //   - recipients: array of contacts
  crmMailing2.controller('PreviewRecipCtrl', function ($scope) {
    $scope.ts = CRM.ts('CiviMail');
  });
})(angular, CRM.$, CRM._);
