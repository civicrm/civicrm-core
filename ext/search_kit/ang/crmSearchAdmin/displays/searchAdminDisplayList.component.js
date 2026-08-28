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
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Display'});

      this.getColTypes = function() {
        return ctrl.parent.colTypes;
      };

      // Grouping is a client-side band over already-sorted rows, so the group_by
      // field must always be the primary sort key or the bands will be wrong.
      // Also incompatible with the pager - a group can straddle a page boundary,
      // which would show its header again partway through the same group.
      this.setGroupBy = function(field) {
        ctrl.display.settings.group_by = field;
        ctrl.display.settings.sort = (ctrl.display.settings.sort || []).filter(s => s[0] !== field);
        if (field) {
          ctrl.display.settings.sort.unshift([field, 'ASC']);
          ctrl.display.settings.pager = false;
          ctrl.display.settings.header_tag = ctrl.display.settings.header_tag || 'h4';
        }
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
        if (ctrl.display.settings.group_by) {
          ctrl.display.settings.header_tag = ctrl.display.settings.header_tag || 'h4';
        }
        ctrl.parent.initColumns({});
      };

    }
  });

})(angular, CRM.$, CRM._);
