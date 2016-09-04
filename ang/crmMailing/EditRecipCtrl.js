(function(angular, $, _) {

  // Controller for the edit-recipients fields (
  // WISHLIST: Move most of this to a (cache-enabled) service
  // Scope members:
  //  - [input] mailing: object
  //  - [output] recipients: array of recipient records
  angular.module('crmMailing').controller('EditRecipCtrl', function EditRecipCtrl($scope, dialogService, crmApi, crmMailingMgr, $q, crmMetadata, crmStatus) {
    // Time to wait before triggering AJAX update to recipients list
    var RECIPIENTS_DEBOUNCE_MS = 100;
    var RECIPIENTS_PREVIEW_LIMIT = 50;

    var ts = $scope.ts = CRM.ts(null);

    $scope.isMailingList = function isMailingList(group) {
      var GROUP_TYPE_MAILING_LIST = '2';
      return _.contains(group.group_type, GROUP_TYPE_MAILING_LIST);
    };

    $scope.recipients = null;
    $scope.getRecipientsEstimate = function() {
      var ts = $scope.ts;
      if ($scope.recipients === null) {
        return ts('(Estimating)');
      }
      if ($scope.recipients === 0) {
        return ts('No recipients');
      }
      if ($scope.recipients === 1) {
        return ts('~1 recipient');
      }
      return ts('~%1 recipients', {1: $scope.recipients});
    };

    // We monitor four fields -- use debounce so that changes across the
    // four fields can settle-down before AJAX.
    var refreshRecipients = _.debounce(function() {
      $scope.$apply(function() {
        $scope.recipients = null;
        if (!$scope.mailing) {
          return;
        }
        crmMailingMgr.previewRecipientCount($scope.mailing).then(function(recipients) {
          $scope.recipients = recipients;
        });
      });
    }, RECIPIENTS_DEBOUNCE_MS);
    $scope.$watchCollection("mailing.dedupe_email", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.groups.include", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.groups.exclude", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.mailings.include", refreshRecipients);
    $scope.$watchCollection("mailing.recipients.mailings.exclude", refreshRecipients);

    $scope.previewRecipients = function previewRecipients() {
      return crmStatus({start: ts('Previewing...'), success: ''}, crmMailingMgr.previewRecipients($scope.mailing, RECIPIENTS_PREVIEW_LIMIT).then(function(recipients) {
        var model = {
          count: $scope.recipients,
          sample: recipients,
          sampleLimit: RECIPIENTS_PREVIEW_LIMIT
        };
        var options = CRM.utils.adjustDialogDefaults({
          width: '40%',
          autoOpen: false,
          title: ts('Preview (%1)', {
            1: $scope.getRecipientsEstimate()
          })
        });
        dialogService.open('recipDialog', '~/crmMailing/PreviewRecipCtrl.html', model, options);
      }));
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
