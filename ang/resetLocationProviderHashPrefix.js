(function(angular, $, _) {

  // dev/core#1818 use angular 1.5 default of # instead of 1.6+ default of #!
  angular.module('ng')
    .config(['$locationProvider', function($locationProvider) {
      $locationProvider.hashPrefix('');
    }]);

})(angular, CRM.$, CRM._);
