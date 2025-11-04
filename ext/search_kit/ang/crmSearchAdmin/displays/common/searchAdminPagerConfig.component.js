(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminPagerConfig', {
    bindings: {
      display: '<',
      noLimit: '<',
    },
    templateUrl: '~/crmSearchAdmin/displays/common/searchAdminPagerConfig.html',
    controller: function($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      function getDefaultSettings() {
        return _.cloneDeep({
          show_count: false,
          expose_limit: false
        });
      }

      this.$onInit = function() {
        // Legacy support
        if (this.display.settings.pager === true) {
          this.display.settings.pager = getDefaultSettings();
        }
        if (this.display.settings.pager && !this.display.settings.limit) {
          this.toggleLimit();
        }
      };

      this.togglePager = function() {
        this.display.settings.pager = this.display.settings.pager ? false : getDefaultSettings();
        if (this.display.settings.pager && !this.display.settings.limit) {
          this.toggleLimit();
        } else if (!this.display.settings.pager && this.noLimit) {
          delete this.display.settings.limit;
        }
      };

      this.toggleLimit = function() {
        if (ctrl.display.settings.limit) {
          ctrl.display.settings.limit = 0;
        } else {
          ctrl.display.settings.limit = CRM.crmSearchAdmin.defaultPagerSize;
        }
      };

      // When user deletes limit, set it to 0 and disable pager
      this.onChangeLimit = function() {
        if (!ctrl.display.settings.limit) {
          ctrl.display.settings.limit = 0;
          ctrl.display.settings.pager = false;
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
