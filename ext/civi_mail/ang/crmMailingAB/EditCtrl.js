(function(angular, $, _) {

  angular.module('crmMailingAB').controller('CrmMailingABEditCtrl', function($scope, abtest, crmMailingABCriteria, crmMailingMgr, crmMailingPreviewMgr, crmStatus, $q, $location, crmBlocker, $interval, $timeout, CrmAutosaveCtrl, dialogService, mailingFields) {
    $scope.abtest = abtest;
    var ts = $scope.ts = CRM.ts('civi_mail');
    var block = $scope.block = crmBlocker();
    $scope.crmUrl = CRM.url;
    var myAutosave = null;
    $scope.crmMailingABCriteria = crmMailingABCriteria;
    $scope.crmMailingConst = CRM.crmMailing;
    $scope.checkPerm = CRM.checkPerm;
    $scope.mailingFields = mailingFields;

    $scope.isSubmitted = function isSubmitted() {
      return _.size(abtest.mailings.a.jobs) > 0 || _.size(abtest.mailings.b.jobs) > 0;
    };

    $scope.sync = function sync() {
      abtest.mailings.a.name = ts('Test A (%1)', {1: abtest.ab.name});
      abtest.mailings.b.name = ts('Test B (%1)', {1: abtest.ab.name});
      abtest.mailings.c.name = ts('Final (%1)', {1: abtest.ab.name});

      if (abtest.ab.testing_criteria) {
        // TODO review fields exposed in UI and make sure the sync rules match
        switch (abtest.ab.testing_criteria) {
          case 'subject':
            var exclude_subject = [
              'name',
              'recipients',
              'subject'
            ];
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, exclude_subject);
            crmMailingMgr.mergeInto(abtest.mailings.c, abtest.mailings.a, exclude_subject);
            break;
          case 'from':
            var exclude_from = [
              'name',
              'recipients',
              'from_name',
              'from_email'
            ];
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, exclude_from);
            crmMailingMgr.mergeInto(abtest.mailings.c, abtest.mailings.a, exclude_from);
            break;
          case 'full_email':
            var exclude_full_email = [
              'name',
              'recipients',
              'subject',
              'from_name',
              'from_email',
              'replyto_email',
              'override_verp', // keep override_verp and replyto_Email linked
              'body_html',
              'body_text'
            ];
            crmMailingMgr.mergeInto(abtest.mailings.b, abtest.mailings.a, exclude_full_email);
            crmMailingMgr.mergeInto(abtest.mailings.c, abtest.mailings.a, exclude_full_email);
            break;
          default:
            throw "Unrecognized testing_criteria";
        }
      }
      return true;
    };

    // @return Promise
    $scope.save = function save() {
      return block(crmStatus({start: ts('Saving...'), success: ts('Saved')}, abtest.save()));
    };

    // @return Promise
    $scope.previewMailing = function previewMailing(mailingName, mode) {
      return crmMailingPreviewMgr.preview(abtest.mailings[mailingName], mode);
    };

    // @return Promise
    $scope.sendTest = function sendTest(mailingName, recipient) {
      return block(crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
        .then(function() {
          crmMailingPreviewMgr.sendTest(abtest.mailings[mailingName], recipient);
        }));
    };

    // @return Promise
    $scope.delete = function() {
      return block(crmStatus({start: ts('Deleting...'), success: ts('Deleted')}, abtest.delete().then($scope.leave)));
    };

    // @return Promise
    $scope.submit = function submit() {
      if (block.check() || $scope.crmMailingAB.$invalid) {
        return;
      }
      return block(crmStatus({start: ts('Saving...'), success: ''}, abtest.save())
          .then(function() {
            return crmStatus({
              start: ts('Submitting...'),
              success: ts('Submitted')
            }, myAutosave.suspend(abtest.submitTest()));
            // Note: We're going to leave, so we don't care that submit() modifies several server-side records.
            // If we stayed on this page, then we'd care about updating and call: abtest.submitTest().then(...abtest.load()...)
          })
      );
    };

    $scope.leave = function leave() {
      $location.path('abtest');
      $location.replace();
    };

    $scope.selectWinner = function selectWinner(mailingName) {
      var model = {
        abtest: $scope.abtest,
        mailingName: mailingName
      };
      var options = CRM.utils.adjustDialogDefaults({
        autoOpen: false,
        height: 'auto',
        width: '40%',
        title: ts('Select Final Mailing (Test %1)', {
          1: mailingName.toUpperCase()
        })
      });
      return myAutosave.suspend(dialogService.open('selectWinnerDialog', '~/crmMailingAB/WinnerDialogCtrl.html', model, options));
    };

    // initialize
    var syncJob = $interval($scope.sync, 333);
    $scope.$on('$destroy', function() {
      $interval.cancel(syncJob);
    });

    myAutosave = new CrmAutosaveCtrl({
      save: $scope.save,
      saveIf: function() {
        return abtest.ab.status == 'Draft' && $scope.sync();
      },
      model: function() {
        return abtest.getAutosaveSignature();
      },
      form: function() {
        return $scope.crmMailingAB;
      }
    });
    $timeout(myAutosave.start);
    $scope.$on('$destroy', myAutosave.stop);
  });

})(angular, CRM.$, CRM._);
