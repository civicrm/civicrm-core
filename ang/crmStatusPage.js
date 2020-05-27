(function(angular, $, _) {
  angular.module('statuspage', CRM.angRequires('statuspage'));

  // router
  angular.module('statuspage').config( function($routeProvider) {
    $routeProvider.when('/status', {
      controller: 'statuspageStatusPage',
      templateUrl: '~/statuspage/StatusPage.html',

      resolve: {
        statusData: function(crmApi) {
          return crmApi('System', 'check', {sequential: 1});
        }
      }
    });

  }
);
})(angular, CRM.$, CRM._);
