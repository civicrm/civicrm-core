(function(angular, $, _) {

  // Display a list of user-driven message-templates.
  angular.module('msgtplui').config(function($routeProvider) {
      $routeProvider.when('/user', {
        reloadOnSearch: false,
        controller: 'MsgtpluiListCtrl',
        controllerAs: '$ctrl',
        templateUrl: '~/msgtplui/User.html',
        resolve: {
          prefetch: function(crmApi4, crmStatus) {
            var q = crmApi4({
              records: ['MessageTemplate', 'get', {
                select: ["id", "msg_title", "msg_subject", "is_active"],
                where: [["workflow_name", "IS EMPTY"], ["is_reserved", "=", "0"]]
              }]
            });
            return crmStatus({start: ts('Loading...'), success: ''}, q);
          }
        }
      });
    }
  );

})(angular, CRM.$, CRM._);
