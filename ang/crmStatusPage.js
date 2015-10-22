(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('statuspage', [
    'crmUi', 'crmUtil', 'ngRoute'
  ]);

  // router

  angular.module('statuspage').config( function($routeProvider) {
    $routeProvider.when('/status', {
      controller: 'statuspageStatusPage',
      templateUrl: '~/statuspage/StatusPage.html',

      resolve: {
        statuses: function(statuspageGetStatuses) {
          return statuspageGetStatuses({sequential: 1});
        },
        statusModel: function(statuspageStatusModel) {
          return statuspageStatusModel();
        },
        preferences: function(statuspageGetPreferences){
          return statuspageGetPreferences();
        }
      }
    });

  }
);
})(angular, CRM.$, CRM._);
