(function(angular, $) {
  angular.module('crmStatusPage', CRM.angRequires('crmStatusPage'));

  // router
  angular.module('crmStatusPage').config( function($routeProvider) {
    $routeProvider.when('/status', {
      controller: 'crmStatusPageCtrl',
      templateUrl: '~/crmStatusPage/StatusPage.html'
    });

  }
);
})(angular, CRM.$);
