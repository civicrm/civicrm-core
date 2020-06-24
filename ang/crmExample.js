(function(angular, $, _) {

  angular.module('crmExample', CRM.angRequires('crmExample'));

  angular.module('crmExample').config([
    '$routeProvider',
    function($routeProvider) {
      $routeProvider.when('/example', {
        templateUrl: '~/crmExample/example.html',
        controller: 'ExampleCtrl'
      });
    }
  ]);

  angular.module('crmExample').controller('ExampleCtrl', function ExampleCtrl($scope) {
    $scope.ts = CRM.ts(null);

    //$scope.examples = {
    //  blank1: {value: '', required: false},
    //  blank2: {value: '', required: true},
    //  filled1: {value:'2014-01-02', required: false},
    //  filled2: {value:'2014-02-03', required: true}
    //};

    //$scope.examples = {
    //  blank1: {value: '', required: false},
    //  blank2: {value: '', required: true},
    //  filled1: {value:'12:34', required: false},
    //  filled2: {value:'10:09', required: true}
    //};

    $scope.examples = {
      blank: {value: '', required: false},
      //blankReq: {value: '', required: true},
      filled: {value:'2014-01-02 03:04', required: false},
      //filledReq: {value:'2014-02-03 05:06', required: true},
      missingDate: {value:' 05:06', required: false},
      //missingDateReq: {value:' 05:06', required: true},
      missingTime: {value:'2014-03-04 ', required: false}
      //missingTimeReq: {value:'2014-03-04 ', required: true}
    };

  });

})(angular, CRM.$, CRM._);
