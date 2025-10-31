(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminPlaceholderConfig', {
    bindings: {
      display: '<',
    },
    templateUrl: '~/crmSearchAdmin/displays/common/searchAdminPlaceholderConfig.html',
    controller: function($scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        // Legacy support
        if (!('placeholder' in this.display.settings)) {
          this.display.settings.placeholder = 5;
        }
      };

      this.togglePlaceholder = function() {
        this.display.settings.placeholder = this.display.settings.placeholder ? 0 : 5;
      };

    }
  });

})(angular, CRM.$, CRM._);
