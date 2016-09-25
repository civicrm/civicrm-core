(function(angular, $, _) {

  angular.module('statuspage').controller('statuspageStatusPage',
    function($scope, crmApi, crmStatus, statusData) {
      $scope.ts = CRM.ts();
      $scope.help = CRM.help;
      $scope.formatDate = CRM.utils.formatDate;
      $scope.statuses = statusData.values;

      // updates a status preference and refreshes status data
      $scope.setPref = function(status, until, visible) {
        // Use an array because it's important that one api call executes before the other
        var apiCalls = [
          ['StatusPreference', 'create', {
              "name": status.name,
              "ignore_severity": visible ? 0 : status.severity,
              "hush_until": until
            }],
          ['System', 'check', {sequential: 1}]
        ];
        crmApi(apiCalls, true)
          .then(function(result) {
            $scope.statuses = result[1].values;
          });
      };
      
      $scope.countVisible = function(visibility) {
        return _.filter($scope.statuses, function(s) {
          return s.is_visible == visibility && s.severity_id >= 2;
        }).length;
      };
    });

})(angular, CRM.$, CRM._);
