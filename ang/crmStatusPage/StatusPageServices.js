(function(angular, $, _) {

  angular.module('statuspage').filter('trusted', function($sce){ return $sce.trustAsHtml; });

  angular.module('statuspage').service('statuspageSeverityList', function() {
    return ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'];
  });

})(angular, CRM.$, CRM._);
