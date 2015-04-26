(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('crmSetting', [
    'crmUi', 'crmUtil', 'ngRoute'
  ]);
  
  angular.module('crmSetting').config([
    '$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/settings', {
        controller: 'AdminSettingCtrl',
        templateUrl: '~/crmSetting/SettingCtrl.html',

        // If you need to look up data when opening the page, list it out
        // under "resolve".
        resolve: {
          myContact: function(crmApi) {
            return crmApi('Contact', 'getsingle', {
              id: 'user_contact_id',
              return: ['first_name', 'last_name']
            });
          }
        } 
      });
    }
  ]);
  
})(angular, CRM.$, CRM._);
