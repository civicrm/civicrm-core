(function(angular, $, _) {

  angular.module('msgtplui').config(function($routeProvider) {
      $routeProvider.when('/workflow', {
        controller: 'MsgtpluiWorkflow',
        controllerAs: '$ctrl',
        templateUrl: '~/msgtplui/Workflow.html',
        resolve: {
          records: function(crmApi4) {
            return crmApi4('MessageTemplate', 'get', {
              select: ["id", "msg_title", "tx.language:label", "tx.language", "is_default"],
              join: [["Translation AS tx", "LEFT", null, ["tx.entity_table", "=", "'civicrm_msg_template'"], ["tx.entity_id", "=", "id"]]],
              where: [["workflow_name", "IS NOT EMPTY"]],
              groupBy: ["id", "tx.language"],
              orderBy: {"msg_title":"ASC"},
              chain: {"statuses":["Translation", "get", {"select":["status_id:name"], "where":[["entity_table", "=", "civicrm_msg_template"], ["entity_id", "=", "$id"], ["language", "=", "$tx.language"]], "groupBy":["status_id"]}, "status_id:name"]}
            });
          },
        },
      });
    }
  );

  // The controller uses *injection*. This default injects a few things:
  //   $scope -- This is the set of variables shared between JS and HTML.
  //   crmApi, crmStatus, crmUiHelp -- These are services provided by civicrm-core.
  //   myContact -- The current contact, defined above in config().
  angular.module('msgtplui').controller('MsgtpluiWorkflow', function($scope, crmStatus, crmUiHelp, records) {
    var ts = $scope.ts = CRM.ts('msgtplui');
    var hs = $scope.hs = crmUiHelp({file: 'CRM/msgtplui/Workflow'}); // See: templates/CRM/msgtplui/Workflow.hlp
    var ctrl = this;
    ctrl.records = records;
  });

})(angular, CRM.$, CRM._);
