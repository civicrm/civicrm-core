(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayList', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayList.html',
    controller: function($scope) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.symbols = {
        ul: [
          {char: '', label: ts('Default')},
          {char: 'circle', label: ts('Circles')},
          {char: 'square', label: ts('Squares')},
          {char: 'none', label: ts('None')},
        ],
        ol: [
          {char: '', label: ts('Default (1. 2. 3.)')},
          {char: 'upper-latin', label: ts('Uppercase (A. B. C.)')},
          {char: 'lower-latin', label: ts('Lowercase (a. b. c.)')},
          {char: 'upper-roman', label: ts('Roman (I. II. III.)')},
        ]
      };

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = {
            style: 'ul',
            limit: CRM.crmSearchAdmin.defaultPagerSize,
            pager: {}
          };
        }
        ctrl.parent.initColumns({});
      };

    }
  });

})(angular, CRM.$, CRM._);
