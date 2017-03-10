(function (angular, $, _) {

  angular.module('crmCxn', [
    'crmUtil', 'ngRoute', 'ngSanitize', 'ui.utils', 'crmUi', 'dialogService'
  ]);

  angular.module('crmCxn').config([
    '$routeProvider',
    function ($routeProvider) {
      $routeProvider.when('/cxn', {
        templateUrl: '~/crmCxn/ManageCtrl.html',
        controller: 'CrmCxnManageCtrl',
        resolve: {
          apiCalls: function(crmApi){
            var reqs = {};
            reqs.cxns = ['Cxn', 'get', {sequential: 1}];
            reqs.appMetas = ['CxnApp', 'get', {sequential: 1, return: ['id', 'title', 'desc', 'appId', 'appUrl', 'links', 'perm']}];
            reqs.cfg = ['Cxn', 'getcfg', {}];
            reqs.sysCheck = ['System', 'check', {}]; // FIXME: filter on checkCxnOverrides
            return crmApi(reqs);
          }
        }
      });
    }
  ]);

})(angular, CRM.$, CRM._);
