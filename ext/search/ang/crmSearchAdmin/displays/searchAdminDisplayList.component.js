(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayList', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdminDisplay: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayList.html',
    controller: function($scope, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;
      this.getFieldLabel = searchMeta.getDefaultLabel;

      this.sortableOptions = {
        connectWith: '.crm-search-admin-edit-columns',
        containment: '.crm-search-admin-edit-columns-wrapper'
      };

      this.removeCol = function(index) {
        ctrl.hiddenColumns.push(ctrl.display.settings.columns[index]);
        ctrl.display.settings.columns.splice(index, 1);
      };

      this.restoreCol = function(index) {
        ctrl.display.settings.columns.push(ctrl.hiddenColumns[index]);
        ctrl.hiddenColumns.splice(index, 1);
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
            limit: 20,
            pager: true
          };
        }
        ctrl.hiddenColumns = ctrl.crmSearchAdminDisplay.initColumns();
        ctrl.links = ctrl.crmSearchAdminDisplay.getLinks();
      };

    }
  });

})(angular, CRM.$, CRM._);
