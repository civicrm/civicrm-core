(function(angular, $, _) {
  "use strict";

  // This isn't a real display type, it's only used for preview purposes on the Admin screen
  angular.module('crmSearchAdmin').component('crmSearchDisplayEntity', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      settings: '<',
    },

    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayTable.html',
    controller: function($scope, $element, searchDisplayBaseTrait) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        // Mix in a copy of searchDisplayBaseTrait
        ctrl = angular.extend(this, _.cloneDeep(searchDisplayBaseTrait));

      this.$onInit = function() {
        // Adding this stuff for the sake of preview, but pollutes the display settings
        // so it gets removed by preSaveDisplay hook
        this.settings.limit = 50;
        this.settings.pager = {expose_limit: true};
        this.settings.classes = ['table', 'table-striped'];
        this.initializeDisplay($scope, $element);
      };

    }
  });

})(angular, CRM.$, CRM._);
