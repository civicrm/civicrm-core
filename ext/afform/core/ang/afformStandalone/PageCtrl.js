(function(angular, $, _) {

  angular.module('afformStandalone').config(function($routeProvider) {
      $routeProvider.when('/', {
        controller: 'AfformStandalonePageCtrl',
        template: function() {return '<div ' + CRM.afform.open + '="{}"></div>'; }
      });
    }
  );

  angular.module('afformStandalone').controller('AfformStandalonePageCtrl', function($scope) {});

})(angular, CRM.$, CRM._);
