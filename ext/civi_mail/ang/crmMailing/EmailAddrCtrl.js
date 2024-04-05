(function(angular, $, _) {

  angular.module('crmMailing').controller('EmailAddrCtrl', function EmailAddrCtrl($scope, crmFromAddresses, crmUiAlert) {
    var ts = CRM.ts('civi_mail');

    function changeAlert(winnerField, loserField) {
      crmUiAlert({
        title: ts('Conflict'),
        text: ts('The "%1" option conflicts with the "%2" option. The "%2" option has been disabled.', {
          1: winnerField,
          2: loserField
        })
      });
    }

    $scope.crmFromAddresses = crmFromAddresses;
    $scope.checkReplyToChange = function checkReplyToChange(mailing) {
      if (!_.isEmpty(mailing.replyto_email) && mailing.override_verp == '0') {
        mailing.override_verp = '1';
        changeAlert(ts('Reply-To'), ts('Track Replies'));
      }
    };
    $scope.checkVerpChange = function checkVerpChange(mailing) {
      if (!_.isEmpty(mailing.replyto_email) && mailing.override_verp == '0') {
        mailing.replyto_email = '';
        changeAlert(ts('Track Replies'), ts('Reply-To'));
      }
    };
  });

})(angular, CRM.$, CRM._);
