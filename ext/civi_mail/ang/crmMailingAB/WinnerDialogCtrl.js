(function(angular, $, _) {

  angular.module('crmMailingAB').controller('CrmMailingABWinnerDialogCtrl', function($scope, $timeout, dialogService, crmMailingMgr, crmStatus) {
    var ts = $scope.ts = CRM.ts('civi_mail');
    var abtest = $scope.abtest = $scope.model.abtest;
    var mailingName = $scope.model.mailingName;

    var titles = {a: ts('Mailing A'), b: ts('Mailing B')};
    $scope.mailingTitle = titles[mailingName];

    function init() {
      // When using dialogService with a button bar, the major button actions
      // need to be registered with the dialog widget (and not embedded in
      // the body of the dialog).
      var buttons = [
        {
          text: ts('Submit final mailing'),
          icons: {primary: 'fa-paper-plane'},
          click: function() {
            crmStatus({start: ts('Submitting...'), success: ts('Submitted')},
              abtest.submitFinal(abtest.mailings[mailingName].id).then(function (r) {
                delete abtest.$CrmMailingABReportCnt;
              }))
              .then(function () {
                dialogService.close('selectWinnerDialog', abtest);
              });
          }
        },
        {
          text: ts('Cancel'),
          icons: {primary: 'fa-times'},
          click: function() {
            dialogService.cancel('selectWinnerDialog');
          }
        }
      ];
      dialogService.setButtons('selectWinnerDialog', buttons);
    }

    $timeout(init);
  });

})(angular, CRM.$, CRM._);
