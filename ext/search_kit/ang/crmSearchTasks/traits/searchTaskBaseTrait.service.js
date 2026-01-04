(function(angular, $, _) {
  "use strict";

  // Trait shared by task controllers
  angular.module('crmSearchTasks').factory('searchTaskBaseTrait', function(dialogService) {
    const ts = CRM.ts('org.civicrm.search_kit');

    // Trait properties get mixed into task controller using angular.extend()
    return {

      getEntityTitle: function(count) {
        if (typeof count !== 'number') {
          count = this.ids.length;
        }
        return count === 1 ? this.entityInfo.title : this.entityInfo.title_plural;
      },

      start: function(runParams) {
        $('.ui-dialog-titlebar button').hide();
        this.run = runParams || {};
      },

      cancel: function() {
        dialogService.cancel('crmSearchTask');
      },

      close: function(result) {
        dialogService.close('crmSearchTask', result);
      }

    };
  });

})(angular, CRM.$, CRM._);
