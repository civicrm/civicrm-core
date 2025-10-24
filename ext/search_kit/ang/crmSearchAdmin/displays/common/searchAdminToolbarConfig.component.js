(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminToolbarConfig', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/displays/common/searchAdminToolbarConfig.html',
    controller: function($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        this.links = ctrl.crmSearchAdmin.buildLinks(false);
        // Migrate legacy setting
        if (ctrl.display.settings.addButton) {
          if (!ctrl.display.settings.toolbar && ctrl.display.settings.addButton.path) {
            ctrl.display.settings.addButton.style = 'primary';
            ctrl.display.settings.toolbar = [ctrl.display.settings.addButton];
          }
          delete ctrl.display.settings.addButton;
        }
      };

      this.toggleToolbar = function() {
        if (ctrl.display.settings.toolbar) {
          delete ctrl.display.settings.toolbar;
        } else {
          ctrl.display.settings.toolbar = _.filter(ctrl.links, function(link) {
            return link.action === 'add' && !link.join;
          });
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
