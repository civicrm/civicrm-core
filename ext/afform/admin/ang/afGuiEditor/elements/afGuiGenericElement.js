// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Generic element handler for element types supplied by 3rd-party extensions
  // If they have no configuration options they can use the generic template,
  // or they can supply their own `admin_tpl` path.
  angular.module('afGuiEditor').component('afGuiGenericElement', {
    template: '<div ng-include="$ctrl.getTemplate()"></div>',
    bindings: {
      node: '=',
      deleteThis: '&'
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this,
        elementType = {};

      this.$onInit = function() {
        elementType = _.findWhere(afGui.meta.elements, {directive: ctrl.node['#tag']});
      };

      this.getTemplate = function() {
        return elementType.admin_tpl || '~/afGuiEditor/elements/afGuiGenericElement.html';
      };

      this.getTitle = function() {
        return elementType.title;
      };
    }
  });

})(angular, CRM.$, CRM._);
