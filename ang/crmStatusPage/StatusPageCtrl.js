(function(angular, $, _) {

  angular.module('statuspage').controller('statuspageStatusPage',
    function($scope, crmApi, crmStatus, statusData, statuspageSeverityList) {

      var ts = $scope.ts = CRM.ts();
      $scope.alert = CRM.alert;
      $scope.statuses = statusData.values;

      _.each($scope.statuses, function(status) {
        status.severity_id = status.severity;
        status.severity = statuspageSeverityList[status.severity];
      });

      // updates a status preference
      $scope.setPref = function(status, until, visible) {
        crmApi('StatusPreference', 'create', {
          "name": status.name,
          "ignore_severity": visible ? 0 : status.severity,
          "hush_until": until
        }, true)
          .then(function() {
            status.is_visible = visible;
          });
      };
      
      $scope.countVisible = function(visibility) {
        return _.filter($scope.statuses, function(s) {
          return s.is_visible == visibility && s.severity_id >= 3;
        }).length;
      };
    });

})(angular, CRM.$, CRM._);
