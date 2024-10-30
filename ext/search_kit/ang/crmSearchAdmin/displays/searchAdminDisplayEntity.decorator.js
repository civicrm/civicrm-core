(function(angular, $, _) {
  "use strict";

  // Register hooks on the crmSearchAdmin component
  angular.module('crmSearchAdmin').decorator('crmSearchAdminDirective', function($delegate, crmApi4) {
    // Register callback for preSaveDisplay hook
    $delegate[0].controller.hook.preSaveDisplay.push(function(display, apiCalls) {
      if (display.type === 'entity') {
        // Unset vars added by the preview (see `crmSearchDisplayEntity`)
        delete display.settings.limit;
        delete display.settings.pager;
        delete display.settings.classes;
      }
      if (display.type === 'entity' && display._job) {
        // Add/update scheduled job
        display._job.api_entity = 'SK_' + display.name;
        display._job.api_action = 'refresh';
        display._job.name = ts('Refresh %1 Table', {1: display.label});
        display._job.description = ts('Refresh contents of the %1 SearchKit entity', {1: display.label});
        apiCalls['job_' + display.name] = ['Job', 'save', {
          records: [display._job],
          match: ['api_entity', 'api_action']
        }, 0];
      }
    });
    // Register callback for postSaveDisplay hook
    $delegate[0].controller.hook.postSaveDisplay.push(function(display, apiResults) {
      if (display.type === 'entity') {
        // Refresh entity displays which write to SQL tables. Do this asynchronously because it can be slow.
        crmApi4('SK_' + display.name, 'refresh', {}, 0).then(function(result) {
          display._refresh_date = CRM.utils.formatDate(result.refresh_date, null, true);
        });
        // Job was a separate api call, add its result back in to the model
        if (apiResults['job_' + display.name]) {
          display._job = apiResults['job_' + display.name];
        }
        // Refresh admin settings to reflect any new/updated entity + joins
        fetch(CRM.url('civicrm/ajax/admin/search'))
          .then(response => response.json())
          .then(data => CRM.crmSearchAdmin = data);
      }
    });
    return $delegate;
  });

})(angular, CRM.$, CRM._);
