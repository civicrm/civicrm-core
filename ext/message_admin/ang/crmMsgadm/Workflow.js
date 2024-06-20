(function(angular, $, _) {

  // Display a list of system-workflow message-templates.
  angular.module('crmMsgadm').config(function($routeProvider) {
      $routeProvider.when('/workflow', {
        reloadOnSearch: false,
        controller: 'MsgtpluiListCtrl',
        controllerAs: '$ctrl',
        templateUrl: function() {
          var supportsTranslation = CRM.crmMsgadm.allLanguages && _.size(CRM.crmMsgadm.allLanguages) > 1;
          return supportsTranslation ? '~/crmMsgadm/WorkflowTranslated.html' : '~/crmMsgadm/Workflow.html';
        },
        resolve: {
          prefetch: function(crmApi4, crmStatus) {
            var q = crmApi4({
              records: ['MessageTemplate', 'get', {
                select: ["id", "msg_title", "is_default", "is_active", "workflow_name", 'master_id'],
                where: [["workflow_name", "IS NOT EMPTY"], ["is_reserved", "=", "0"]]
              }],
              translations: ['MessageTemplate', 'get', {
                select: ["id", "msg_title", "is_default", "is_active", "workflow_name", "tx.language:label", "tx.language"],
                join: [["Translation AS tx", "INNER", null, ["tx.entity_table", "=", "'civicrm_msg_template'"], ["tx.entity_id", "=", "id"]]],
                where: [["workflow_name", "IS NOT EMPTY"], ["is_reserved", "=", "0"]],
                groupBy: ["id", "tx.language"],
                chain: {"tx.statuses":["Translation", "get", {"select":["status_id:name"], "where":[["entity_table", "=", "civicrm_msg_template"], ["entity_id", "=", "$id"], ["language", "=", "$tx.language"]], "groupBy":["status_id"]}, "status_id:name"]}
              }]
            });
            return crmStatus({start: ts('Loading...'), success: ''}, q);
          }
        },
      });
    }
  );

})(angular, CRM.$, CRM._);
