(function(angular, $, _) {

  angular.module('statuspage').controller('statuspageStatusPage',
    function($scope, crmApi, crmStatus, statusData) {

      function preprocessStatuses(apiData) {
        _.each(apiData.values, function(status) {
          if (status.hidden_until) {
            var date = $.datepicker.parseDate('yy-mm-dd', status.hidden_until);
            status.hidden_until = $.datepicker.formatDate(CRM.config.dateInputFormat, date);
          }
        });
        $scope.statuses = apiData.values;
      }
      preprocessStatuses(statusData);

      $scope.ts = CRM.ts();
      $scope.alert = CRM.alert;

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
            preprocessStatuses(result[1]);
          });
      };
      
      $scope.countVisible = function(visibility) {
        return _.filter($scope.statuses, function(s) {
          return s.is_visible == visibility && s.severity_id >= 3;
        }).length;
      };
    });

})(angular, CRM.$, CRM._);
