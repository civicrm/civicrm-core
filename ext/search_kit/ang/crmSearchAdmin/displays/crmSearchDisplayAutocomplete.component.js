(function(angular, $, _) {
  "use strict";

  // This isn't a real display type, it's only used for preview purposes on the Admin screen
  angular.module('crmSearchAdmin').component('crmSearchDisplayAutocomplete', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
    },
    templateUrl: '~/crmSearchAdmin/displays/searchDisplayAutocomplete.html',
    controller: function($scope, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        this.placeholder = ts('Select %1', {1: searchMeta.getEntity(ctrl.apiEntity).title});
      };

    }
  });

})(angular, CRM.$, CRM._);
