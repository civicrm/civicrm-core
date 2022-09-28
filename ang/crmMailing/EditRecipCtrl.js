(function(angular, $, _) {

  // Controller for the edit-recipients fields (
  // WISHLIST: Move most of this to a (cache-enabled) service
  // Scope members:
  //  - [input] mailing: object
  //  - [output] recipients: array of recipient records
  angular.module('crmMailing').controller('EditRecipCtrl', function EditRecipCtrl($scope, dialogService, crmApi, crmMailingMgr, $q, crmMetadata, crmStatus, crmMailingCache) {
    // Time to wait before triggering AJAX update to recipients list
    var RECIPIENTS_DEBOUNCE_MS = 100;
    var SETTING_DEBOUNCE_MS = 5000;
    var RECIPIENTS_PREVIEW_LIMIT = 50;

    var ts = $scope.ts = CRM.ts(null);

    $scope.isMailingList = function isMailingList(group) {
      var GROUP_TYPE_MAILING_LIST = '2';
      return _.contains(group.group_type, GROUP_TYPE_MAILING_LIST);
    };

    $scope.recipients = null;
    $scope.outdated = null;
    $scope.permitRecipientRebuild = null;

    $scope.getRecipientsEstimate = function() {
      var ts = $scope.ts;
      if ($scope.recipients === null) {
        return ts('Estimating...');
      }
      if ($scope.recipients === 0) {
        return ts('Estimate recipient count');
      }
      return ts('Refresh recipient count');
    };

    $scope.getRecipientCount = function() {
      var ts = $scope.ts;
      if ($scope.recipients === 0) {
        return ts('No Recipients');
      }
      else if ($scope.recipients > 0) {
        return ts('~%1 recipients', {1 : $scope.recipients});
      }
      else if ($scope.outdated) {
        return ts('(unknown)');
      }
      else {
        return $scope.permitRecipientRebuild ? ts('(unknown)') : ts('Estimating...');
      }
    };

    // We monitor four fields -- use debounce so that changes across the
    // four fields can settle-down before AJAX.
    var refreshRecipients = _.debounce(function() {
      $scope.$apply(function() {
        if (!$scope.mailing) {
          return;
        }
        crmMailingMgr.previewRecipientCount($scope.mailing, crmMailingCache, !$scope.permitRecipientRebuild).then(function(recipients) {
          $scope.outdated = ($scope.permitRecipientRebuild && _.difference($scope.mailing.recipients, crmMailingCache.get('mailing-' + $scope.mailing.id + '-recipient-params')) !== 0);
          $scope.recipients = recipients;
        });
      });
    }, RECIPIENTS_DEBOUNCE_MS);
    $scope.$watchCollection("mailing.dedupe_email", refreshRecipients);
    $scope.$watchCollection("mailing.location_type_id", refreshRecipients);
    $scope.$watchCollection("mailing.email_selection_method", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.groups.include", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.groups.exclude", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.mailings.include", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.mailings.exclude", refreshRecipients);

    // refresh setting at a duration on 5sec
    var refreshSetting = _.debounce(function() {
      $scope.$apply(function() {
        crmApi('Setting', 'getvalue', {"name": 'auto_recipient_rebuild', "return": "value"}).then(function(response) {
          $scope.permitRecipientRebuild = (response.result === 0);
        });
      });
    }, SETTING_DEBOUNCE_MS);
    $scope.$watchCollection("permitRecipientRebuild", refreshSetting);

    $scope.previewRecipients = function previewRecipients() {
      var model = {
        count: $scope.recipients,
        sample: crmMailingCache.get('mailing-' + $scope.mailing.id + '-recipient-list'),
        sampleLimit: RECIPIENTS_PREVIEW_LIMIT
      };
      var options = CRM.utils.adjustDialogDefaults({
        width: '40%',
        autoOpen: false,
        title: ts('Preview (%1)', {1: $scope.getRecipientCount()})
      });

      // don't open preview dialog if there is no recipient to show.
      if ($scope.recipients !== 0 && !$scope.outdated) {
        if (!_.isEmpty(model.sample)) {
          dialogService.open('recipDialog', '~/crmMailing/PreviewRecipCtrl.html', model, options);
        }
        else {
          return crmStatus({start: ts('Previewing...'), success: ''}, crmMailingMgr.previewRecipients($scope.mailing, RECIPIENTS_PREVIEW_LIMIT).then(function(recipients) {
            model.sample = recipients;
            dialogService.open('recipDialog', '~/crmMailing/PreviewRecipCtrl.html', model, options);
          }));
        }
      }
    };

    $scope.rebuildRecipients = function rebuildRecipients() {
      // setting null will put 'Estimating..' text on refresh button
      $scope.recipients = null;
      return crmMailingMgr.previewRecipientCount($scope.mailing, crmMailingCache, true).then(function(recipients) {
        $scope.outdated = (recipients === 0) ? true : false;
        $scope.recipients = recipients;
      });
    };

    // Open a dialog for editing the advanced recipient options.
    $scope.editOptions = function editOptions(mailing) {
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        width: '40%',
        height: 'auto',
        title: ts('Edit Options')
      });
      $q.when(crmMetadata.getFields('Mailing')).then(function(fields) {
        var model = {
          fields: fields,
          mailing: mailing
        };
        dialogService.open('previewComponentDialog', '~/crmMailing/EditRecipOptionsDialogCtrl.html', model, options);
      });
    };
  });

})(angular, CRM.$, CRM._);
