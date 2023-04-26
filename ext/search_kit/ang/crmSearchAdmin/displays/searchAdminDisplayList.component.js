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
    controller: function($scope, searchMeta, crmUiHelp) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Display'});

      this.getColTypes = function() {
        return ctrl.parent.colTypes;
      };

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
            limit: ctrl.parent.getDefaultLimit(),
            sort: ctrl.parent.getDefaultSort(),
            pager: {}
          };
        }
        ctrl.parent.initColumns({});
      };

    }
  });

})(angular, CRM.$, CRM._);
