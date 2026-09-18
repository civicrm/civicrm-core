(function(angular, $, _) {

  // Controller for the edit-recipients fields (
  // WISHLIST: Move most of this to a (cache-enabled) service
  // Scope members:
  //  - [input] mailing: object
  //  - [output] recipients: array of recipient records
  angular.module('crmMailing').controller('EditRecipCtrl', function EditRecipCtrl($scope, dialogService, crmApi, crmMailingMgr, $q, crmMetadata, crmStatus, crmMailingCache) {
    // Time to wait before triggering AJAX update to recipients list
    var RECIPIENTS_DEBOUNCE_MS = 100;
    var RECIPIENTS_PREVIEW_LIMIT = 50;

    const ts = $scope.ts = CRM.ts('civi_mail');

    $scope.recipients = null;
    $scope.outdated = null;
    // When the site rebuilds recipients automatically there is nothing manual to do, so the Refresh button and the stale-count marker are both suppressed.
    $scope.permitRecipientRebuild = !CRM.crmMailing.autoRecipientRebuild;

    $scope.getRecipientsEstimate = function() {
      if ($scope.recipients === null) {
        return ts('Estimating...');
      }
      if ($scope.recipients === 0) {
        return ts('Estimate recipient count');
      }
      return ts('Refresh recipient count');
    };

    $scope.getRecipientCount = function() {
      if ($scope.recipients === 0) {
        return ts('No Recipients');
      }
      else if ($scope.recipients > 0) {
        return ts('~%1 recipients', {1 : $scope.recipients});
      }
      return $scope.permitRecipientRebuild ? ts('(unknown)') : ts('Estimating...');
    };

    function builtParamsKey() {
      return 'mailing-' + $scope.mailing.id + '-recipient-params';
    }

    // The inputs a built recipient list depends on - the same ones watched below.
    // Empty values are normalised because the widgets flip between null and '' without changing the query.
    function recipientParams() {
      return {
        recipients: $scope.mailing.recipients,
        dedupe_email: $scope.mailing.dedupe_email || null,
        location_type_id: $scope.mailing.location_type_id || null,
        email_selection_method: $scope.mailing.email_selection_method || null
      };
    }

    // What the current recipient list was built from. Nothing records this against the mailing itself, so a mailing arriving from storage is taken at face value and seeds the snapshot - only edits made here can be detected.
    function builtParams() {
      var built = crmMailingCache.get(builtParamsKey());
      if (!built) {
        built = angular.copy(recipientParams());
        crmMailingCache.put(builtParamsKey(), built);
      }
      return built;
    }

    // We monitor four fields -- use debounce so that changes across the
    // four fields can settle-down before AJAX.
    var refreshRecipients = _.debounce(function() {
      $scope.$apply(function() {
        if (!$scope.mailing) {
          return;
        }
        crmMailingMgr.previewRecipientCount($scope.mailing, crmMailingCache, !$scope.permitRecipientRebuild).then(function(recipients) {
          $scope.outdated = ($scope.permitRecipientRebuild && !angular.equals(recipientParams(), builtParams()));
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
        if (model.sample && model.sample.length) {
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
      // Snapshot what the list is about to be built from, so later edits can be spotted
      crmMailingCache.put(builtParamsKey(), angular.copy(recipientParams()));
      // setting null will put 'Estimating..' text on refresh button
      $scope.recipients = null;
      return crmMailingMgr.previewRecipientCount($scope.mailing, crmMailingCache, true).then(function(recipients) {
        $scope.outdated = false;
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
