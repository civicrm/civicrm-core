(function(angular, $, _) {
  angular.module('crmStatusPage', CRM.angRequires('crmStatusPage'));

  // router
  angular.module('crmStatusPage').config( function($routeProvider) {
    $routeProvider.when('/status', {
      controller: 'crmStatusPageCtrl',
      templateUrl: '~/crmStatusPage/StatusPage.html',

      resolve: {
        statusData: function(crmApi) {
          return crmApi('System', 'check', {sequential: 1});
        }
      }
    });

  }
);
})(angular, CRM.$, CRM._);
