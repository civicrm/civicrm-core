(function(angular, $, _) {

  angular.module('crmMailing').controller('EditMailingCtrl', function EditMailingCtrl($scope, selectedMail, $location, crmMailingMgr, crmStatus, attachments, crmMailingPreviewMgr, crmBlocker, CrmAutosaveCtrl, $timeout, crmUiHelp) {
    var APPROVAL_STATUSES = {'Approved': 1, 'Rejected': 2, 'None': 3};

    $scope.mailing = selectedMail;
    $scope.attachments = attachments;
    $scope.crmMailingConst = CRM.crmMailing;
    $scope.checkPerm = CRM.checkPerm;

    var ts = $scope.ts = CRM.ts(null);
    $scope.hs = crmUiHelp({file: 'CRM/Mailing/MailingUI'});
    var block = $scope.block = crmBlocker();
    var myAutosave = null;

    $scope.isSubmitted = function isSubmitted() {
      return _.size($scope.mailing.jobs) > 0;
    };

    // usage: approve('Approved')
    $scope.approve = function approve(status, options) {
      $scope.mailing.approval_status_id = APPROVAL_STATUSES[status];
      return myAutosave.suspend($scope.submit(options));
    };

    // @return Promise
    $scope.previewMailing = function previewMailing(mailing, mode) {
      return crmMailingPreviewMgr.preview(mailing, mode);
    };

    // @return Promise
    $scope.sendTest = function sendTest(mailing, attachments, recipient) {
      var savePromise = crmMailingMgr.save(mailing)
        .then(function() {
          return attachments.save();
        });
      return block(crmStatus({start: ts('Saving...'), success: ''}, savePromise)
        .then(function() {
          crmMailingPreviewMgr.sendTest(mailing, recipient);
        }));
    };

    // @return Promise
    $scope.submit = function submit(options) {
      options = options || {};
      if (block.check() || $scope.crmMailing.$invalid) {
        return;
      }

      var promise = crmMailingMgr.save($scope.mailing)
          .then(function() {
            // pre-condition: the mailing exists *before* saving attachments to it
            return $scope.attachments.save();
          })
          .then(function() {
            return crmMailingMgr.submit($scope.mailing);
          })
          .then(function() {
            if (!options.stay) {
              $scope.leave('scheduled');
            }
          })
        ;
      return block(crmStatus({start: ts('Submitting...'), success: ts('Submitted')}, promise));
    };

    // @return Promise
    $scope.save = function save() {
      return block(crmStatus(null,
        crmMailingMgr
          .save($scope.mailing)
          .then(function() {
            // pre-condition: the mailing exists *before* saving attachments to it
            return $scope.attachments.save();
          })
      ));
    };

    // @return Promise
    $scope.delete = function cancel() {
      return block(crmStatus({start: ts('Deleting...'), success: ts('Deleted')},
        crmMailingMgr.delete($scope.mailing)
          .then(function() {
            $scope.leave('unscheduled');
          })
      ));
    };

    // @param string listingScreen 'archive', 'scheduled', 'unscheduled'
    $scope.leave = function leave(listingScreen) {
      switch (listingScreen) {
        case 'archive':
          window.location = CRM.url('civicrm/mailing/browse/archived', {
            reset: 1
          });
          break;
        case 'scheduled':
          window.location = CRM.url('civicrm/mailing/browse/scheduled', {
            reset: 1,
            scheduled: 'true'
          });
          break;
        case 'unscheduled':
        /* falls through */
        default:
          window.location = CRM.url('civicrm/mailing/browse/unscheduled', {
            reset: 1,
            scheduled: 'false'
          });
      }
    };

    myAutosave = new CrmAutosaveCtrl({
      save: $scope.save,
      saveIf: function() {
        return true;
      },
      model: function() {
        return [$scope.mailing, $scope.attachments.getAutosaveSignature()];
      },
      form: function() {
        return $scope.crmMailing;
      }
    });
    $timeout(myAutosave.start);
    $scope.$on('$destroy', myAutosave.stop);
  });

})(angular, CRM.$, CRM._);
