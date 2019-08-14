(function(angular, $, _) {

  angular.module('afformStandalone', CRM.angular.modules)
    .config(function($routeProvider) {
      $routeProvider.when('/', {
        controller: 'AfformStandalonePageCtrl',
        template: function() {return '<div ' + CRM.afform.open + '="{}"></div>'; }
      });
    })
    .controller('AfformStandalonePageCtrl', function($scope) {});

})(angular, CRM.$, CRM._);
