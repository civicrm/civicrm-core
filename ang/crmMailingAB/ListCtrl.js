(function(angular, $, _) {

  angular.module('crmMailingAB').controller('CrmMailingABListCtrl', function($scope, mailingABList, crmMailingABCriteria, crmMailingABStatus, fields) {
    var ts = $scope.ts = CRM.ts(null);
    $scope.mailingABList = _.values(mailingABList.values);
    $scope.crmMailingABCriteria = crmMailingABCriteria;
    $scope.crmMailingABStatus = crmMailingABStatus;
    $scope.fields = fields;
    $scope.filter = {};
  });

})(angular, CRM.$, CRM._);
