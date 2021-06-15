(function(angular, $, _) {

  // Display a list of system-workflow message-templates.
  angular.module('msgtplui').config(function($routeProvider) {
      $routeProvider.when('/workflow', {
        reloadOnSearch: false,
        controller: 'MsgtpluiListCtrl',
        controllerAs: '$ctrl',
        templateUrl: '~/msgtplui/Workflow.html',
        resolve: {
          records: function(crmApi4, crmStatus) {
            var q= crmApi4('MessageTemplate', 'get', {
              select: ["id", "msg_title", "tx.language:label", "tx.language", "is_default"],
              join: [["Translation AS tx", "LEFT", null, ["tx.entity_table", "=", "'civicrm_msg_template'"], ["tx.entity_id", "=", "id"]]],
              where: [["workflow_name", "IS NOT EMPTY"]],
              groupBy: ["id", "tx.language"],
              orderBy: {"msg_title":"ASC", "tx.language:label":"ASC"},
              chain: {"statuses":["Translation", "get", {"select":["status_id:name"], "where":[["entity_table", "=", "civicrm_msg_template"], ["entity_id", "=", "$id"], ["language", "=", "$tx.language"]], "groupBy":["status_id"]}, "status_id:name"]}
            });
            return crmStatus({start: ts('Loading...'), success: ''}, q);
          },
        },
      });
    }
  );

})(angular, CRM.$, CRM._);
