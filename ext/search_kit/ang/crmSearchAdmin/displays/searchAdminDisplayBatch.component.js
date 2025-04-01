(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayBatch', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayBatch.html',
    controller: function($scope, searchMeta, crmUiHelp) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      const ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Display'});

      this.includes = _.includes;

      // Add or remove an item from an array
      this.toggle = function(collection, item) {
        if (_.includes(collection, item)) {
          _.pull(collection, item);
        } else {
          collection.push(item);
        }
      };

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            classes: ['table', 'table-striped', 'table-bordered', 'crm-sticky-header'],
          };
        }
        ctrl.parent.initColumns({label: true});
      };

    }
  });

})(angular, CRM.$, CRM._);
