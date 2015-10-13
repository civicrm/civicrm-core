// creates a directive for the snooze options page

(function(angular, $, _) {
  angular.module('statuapage').directive('crmSnoozeOptions', function(statuspageSeverityList) {
    return {
      templateUrl: '~/statuspage/SnoozeOptions.html',
      transclude: true,
      link: function(scope, element, attr) {
        scope.severityList = statuspageSeverityList;
      }
    };
  });
})(angular, CRM.$, CRM._);
