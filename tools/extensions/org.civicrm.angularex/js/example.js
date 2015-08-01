<<<<<<< HEAD
(function(angular, $, _) {

  var resourceUrl = CRM.resourceUrls['org.civicrm.angularex'];
  var example = angular.module('example', ['ngRoute']);

  example.config(['$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/example', {
        templateUrl: resourceUrl + '/partials/example.html',
        controller: 'ExampleCtrl'
      });
    }
  ]);

  example.controller('ExampleCtrl', function($scope) {
    $scope.name = 'world';
  });

=======
(function(angular, $, _) {

  var resourceUrl = CRM.resourceUrls['org.civicrm.angularex'];
  var example = angular.module('example', ['ngRoute', 'crmResource']);

  example.config(['$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/example', {
        templateUrl: '~/example/example.html',
        controller: 'ExampleCtrl'
      });
    }
  ]);

  example.controller('ExampleCtrl', function($scope) {
    $scope.name = 'world';
    $scope.ts = CRM.ts('org.civicrm.angularex');
  });

>>>>>>> 650ff6351383992ec77abface9b7f121f16ae07e
})(angular, CRM.$, CRM._);