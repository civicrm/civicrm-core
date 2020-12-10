(function(angular, $, _) {
  // Declare a list of dependencies.
  angular.module('afformStandalone', CRM.angRequires('afformStandalone'));

  angular.module('afformStandalone', CRM.angular.modules)
    .config(function($routeProvider) {
      $routeProvider.when('/', {
        controller: 'AfformStandalonePageCtrl',
        template: function() {
          return '<div id="bootstrap-theme" ' + CRM.afform.open + '="{}"></div>';
        }
      });
    })
    .controller('AfformStandalonePageCtrl', function($scope) {});

})(angular, CRM.$, CRM._);
