(function(angular, $, _) {

  // controller

  angular.module('statuspage').controller('statuspageStatusPage',
    function($scope, crmApi, crmStatus, crmUiHelp, statuses, preferences) {

    var ts = $scope.ts = CRM.ts();
    $scope.statuses = statuses;
    $scope.preferences = preferences;
    $scope.alert = CRM.alert;

    // will "hush" a status - gets the severity level of the status that is being hushed, and hushes all alerts for that check at and below the level of the current check
    $scope.hush = function(name, severity) {
      return  crmStatus(
        { start: ts('Saving Status Preference...')      , success: ts('Preference Saved') },
        crmApi('StatusPreference', 'create', {
          "sequential": 1,
          "name": name,
          "ignore_severity": severity,
          "hush_until":  ""
        })
        .then(function(){rmStatus($scope, name);})
      );
    };

    // will reset ignore_severity to 0 to unhush the status alert.
    $scope.unhush = function(name, severity) {
      return  crmStatus(
        { start: ts('Saving Status Preference...')      , success: ts('Preference Saved') },
        crmApi('StatusPreference', 'create', {
          "name": name,
          "ignore_severity": 0,
          "hush_until": ""
        })
        .then(function(){rmStatus($scope, name);})
      );
    };

    // will 'snooze' a status - will not show alerts at that level for that check + alerts below that level for that check until the specified date
    $scope.snooze = function(status) {
      $scope.showSnoozeOptions(status);
      return crmStatus(
        { status: ts('Saving Status Preference...')   , success: ts('Preference Saved') },
          crmApi('StatusPreference', 'create', {
            "name": status.name,
            "ignore_severity": status.snoozeOptions.severity,
            "hush_until": status.snoozeOptions.until
          })        .then(function(){rmStatus($scope, status.name);})
      );
    };
    $scope.showSnoozeOptions = function(status) {
      status.snoozeOptions.show = !status.snoozeOptions.show;
    };
  });


  /**
   * remove a status after it has been hushed/snoozed
   * @param {type} $scope
   * @param {type} statusName
   * @returns void
   */
   function rmStatus($scope, statusName) {
    $scope.statuses.values =  _.reject($scope.statuses.values,
      function(status) {
        return status.name === statusName;
    });
  }

})(angular, CRM.$, CRM._);
