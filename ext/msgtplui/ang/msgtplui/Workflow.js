(function(angular, $, _) {

  // Display a list of system-workflow message-templates.
  angular.module('msgtplui').config(function($routeProvider) {
      $routeProvider.when('/workflow', {
        reloadOnSearch: false,
        controller: 'MsgtpluiListCtrl',
        controllerAs: '$ctrl',
        templateUrl: function() {
          // The original drafts had a mode where the "Translate" button was conditioned on some kind of language-opt-in.
          // However, uiLanguages isn't giving that signal anymore, and that opt-in isn't strictly needed since htis
          // is currently packaged as an opt-in extension. Maybe we should just remove `~/msgtplui/Workflow.html` in a few months.
          // But for the moment, keep it around it in case we have to pivot.

          // var supportsTranslation = CRM.msgtplui.uiLanguages && _.size(CRM.msgtplui.uiLanguages) > 1;
          // return supportsTranslation ? '~/msgtplui/WorkflowTranslated.html' : '~/msgtplui/Workflow.html';
          return '~/msgtplui/WorkflowTranslated.html';
        },
        resolve: {
          prefetch: function(crmApi4, crmStatus) {
            var q = crmApi4({
              records: ['MessageTemplate', 'get', {
                select: ["id", "msg_title", "is_default", "is_active"],
                where: [["workflow_name", "IS NOT EMPTY"], ["is_reserved", "=", "0"]]
              }],
              translations: ['MessageTemplate', 'get', {
                select: ["id", "msg_title", "is_default", "is_active", "tx.language:label", "tx.language"],
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
