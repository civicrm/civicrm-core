(function(angular, $, _) {
  "use strict";

  // Trait shared by task controllers
  angular.module('crmSearchDisplay').factory('searchTaskBaseTrait', function(dialogService) {
    var ts = CRM.ts('org.civicrm.search_kit');

    // Trait properties get mixed into task controller using angular.extend()
    return {

      getEntityTitle: function() {
        return this.ids.length === 1 ? this.entityInfo.title : this.entityInfo.title_plural;
      },

      start: function(runParams) {
        $('.ui-dialog-titlebar button').hide();
        this.run = runParams || {};
      },

      cancel: function() {
        dialogService.cancel('crmSearchTask');
      },

      close: function() {
        dialogService.close('crmSearchTask');
      }

    };
  });

})(angular, CRM.$, CRM._);
