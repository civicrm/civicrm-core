(function(angular, $, _) {

  angular.module('msgtplui').config(function($routeProvider) {
      $routeProvider.when('/user', {
        controller: 'MsgtpluiUser',
        controllerAs: '$ctrl',
        templateUrl: '~/msgtplui/User.html',
        resolve: {
          records: function(crmApi4) {
            return crmApi4('MessageTemplate', 'get', {
              select: ["id", "msg_title", "is_default"],
              where: [["workflow_name", "IS EMPTY"]],
              orderBy: {"msg_title":"ASC"},
            });
          }
        }
      });
    }
  );

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   myContact -- The current contact, defined above in config().
  angular.module('msgtplui').controller('MsgtpluiUser', function($scope, crmApi, crmStatus, crmUiHelp, records) {
    var ts = $scope.ts = CRM.ts('msgtplui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/msgtplui/User'}); // See: templates/CRM/msgtplui/User.hlp
    var ctrl = this;
    ctrl.records = records;
  });

})(angular, CRM.$, CRM._);
