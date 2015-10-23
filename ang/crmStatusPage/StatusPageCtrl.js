(function(angular, $, _) {

  angular.module('statuspage').controller('statuspageStatusPage',
    function($scope, crmApi, crmStatus, statusData, statuspageSeverityList) {

      var ts = $scope.ts = CRM.ts();
      $scope.alert = CRM.alert;
      $scope.statuses = statusData.values;

      _.each($scope.statuses, function(status) {
        status.severity_id = status.severity;
        status.severity = statuspageSeverityList[status.severity];
        status.snoozeOptions = {
          show: false,
          severity: status.severity
        };
      });

      // will "hush" a status - gets the severity level of the status that is being hushed, and hushes all alerts for that check at and below the level of the current check
      $scope.hush = function(status) {
        crmApi('StatusPreference', 'create', {
          "name": status.name,
          "ignore_severity": status.severity,
          "hush_until":  ""
        }, true)
          .then(function() {
            status.is_visible = 0;
          });
      };

    // will reset ignore_severity to 0 to unhush the status alert.
    $scope.unhush = function(status) {
      crmApi('StatusPreference', 'create', {
        "name": status.name,
        "ignore_severity": 0,
        "hush_until":  ""
      }, true)
        .then(function() {
          status.is_visible = 1;
        });
    };

      // will 'snooze' a status - will not show alerts at that level for that check + alerts below that level for that check until the specified date
      $scope.snooze = function(status) {
        $scope.showSnoozeOptions(status);
        crmApi('StatusPreference', 'create', {
          "name": status.name,
          "ignore_severity": status.snoozeOptions.severity,
          "hush_until": status.snoozeOptions.until
        }, true)
          .then(function() {
            status.is_visible = 0;
          });
      };

      $scope.showSnoozeOptions = function(status) {
        status.snoozeOptions.show = !status.snoozeOptions.show;
      };
    });

})(angular, CRM.$, CRM._);
