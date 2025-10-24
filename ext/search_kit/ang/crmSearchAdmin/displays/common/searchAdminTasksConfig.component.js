(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminTasksConfig', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    templateUrl: '~/crmSearchAdmin/displays/common/searchAdminTasksConfig.html',
    controller: function($scope, $timeout, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        searchMeta.getSearchTasks(ctrl.apiEntity).then(function(tasks) {
          ctrl.allTasks = tasks;
        });
        // Set default mode for editing old searches that were created before this setting was added
        if (this.display.settings.actions) {
          this.display.settings.actions_display_mode = this.display.settings.actions_display_mode || 'menu';
        }
      };

      this.toggleActions = function() {
        this.display.settings.actions = !this.display.settings.actions;
        if (!this.display.settings.actions) {
          delete this.display.settings.actions_display_mode;
        } else {
          this.display.settings.actions_display_mode = 'menu';
        }
        ctrl.menuOpen = false;
      };

      this.toggleTask = function(name) {
        // Timeout waits for checkbox state to change, otherwise checkbox 'checked' property gets out-of-sync with angular model
        $timeout(function() {
          // Disabling one when all enabled, convert to array
          if (typeof ctrl.display.settings.actions === 'boolean') {
            ctrl.display.settings.actions = _.map(ctrl.allTasks, 'name');
          }
          // Remove enabled task
          if (ctrl.display.settings.actions.includes(name)) {
            _.pull(ctrl.display.settings.actions, name);
          }
          // Add disabled task
          else {
            ctrl.display.settings.actions.push(name);
          }
          // All enabled, convert to boolean
          if (ctrl.display.settings.actions.length === ctrl.allTasks.length) {
            ctrl.display.settings.actions = true;
          }
        });
      };

      this.isEnabled = function(name) {
        return (typeof this.display.settings.actions === 'boolean') || this.display.settings.actions.includes(name);
      };

    }
  });

})(angular, CRM.$, CRM._);
