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

})(angular, CRM.$, CRM._);