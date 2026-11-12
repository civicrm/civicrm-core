(function(angular, $) {

  angular.module('crmMailingAB').controller('CrmMailingABReportCtrl', function($scope, crmApi, crmMailingStats) {
    const ts = $scope.ts = CRM.ts('civi_mail');

    const mailingIds = {};
    ['a', 'b', 'c'].forEach((mkey) => {
      if ($scope.abtest.ab['mailing_id_' + mkey]) {
        mailingIds[mkey] = $scope.abtest.ab['mailing_id_' + mkey];
      }
    });
    // The final mailing can only be sent while A, B and C all exist.
    $scope.hasAllMailings = () => ['a', 'b', 'c'].every((mkey) => mkey in mailingIds);

    let CrmMailingABReportCnt = 1;
    let activeMailings = null;
    $scope.getActiveMailings = function() {
      if ($scope.abtest.$CrmMailingABReportCnt != CrmMailingABReportCnt) {
        $scope.abtest.$CrmMailingABReportCnt = ++CrmMailingABReportCnt;
        activeMailings = [
          {
            name: 'a',
            title: ts('Mailing A'),
            mailing: $scope.abtest.mailings.a,
            attachments: $scope.abtest.attachments.a
          },
          {
            name: 'b',
            title: ts('Mailing B'),
            mailing: $scope.abtest.mailings.b,
            attachments: $scope.abtest.attachments.b
          }
        ];
        if ($scope.abtest.ab.status == 'Final') {
          activeMailings.push({
            name: 'c',
            title: ts('Final'),
            mailing: $scope.abtest.mailings.c,
            attachments: $scope.abtest.attachments.c
          });
        }
        activeMailings.forEach((am) => {
          am.deleted = !(am.name in mailingIds);
          if (am.deleted) {
            am.title = ts('%1 (deleted)', {1: am.title});
          }
        });
      }
      return activeMailings;
    };

    crmMailingStats.getStats(mailingIds).then(function(stats) {
      $scope.stats = stats;
    });
    $scope.rateStats = {
      'Unique Clicks': 'clickthrough_rate',
      'Delivered': 'delivered_rate',
      'Opened': 'opened_rate',
    };
    $scope.statTypes = crmMailingStats.getStatTypes();
    $scope.statUrl = function statUrl(mailing, statType, view) {
      return crmMailingStats.getUrl(mailing, statType, view, 'abtest/' + $scope.abtest.ab.id);
    };

    $scope.checkPerm = CRM.checkPerm;
  });

})(angular, CRM.$);
