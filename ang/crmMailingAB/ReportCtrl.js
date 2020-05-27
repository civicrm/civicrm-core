(function(angular, $, _) {

  angular.module('crmMailingAB').controller('CrmMailingABReportCtrl', function($scope, crmApi, crmMailingStats) {
    var ts = $scope.ts = CRM.ts(null);

    var CrmMailingABReportCnt = 1, activeMailings = null;
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
      }
      return activeMailings;
    };

    crmMailingStats.getStats({
      a: $scope.abtest.ab.mailing_id_a,
      b: $scope.abtest.ab.mailing_id_b,
      c: $scope.abtest.ab.mailing_id_c
    }).then(function(stats) {
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

})(angular, CRM.$, CRM._);
