// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // For configuring autofill by related contact
  angular.module('afCaseGuiEditor').component('autofillCaseBehaviorForm', {
    templateUrl: '~/afCaseGuiEditor/behaviors/autofillCaseBehaviorForm.html',
    bindings: {
      entity: '<',
      selectedType: '<',
      relTypes: '<'
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts('civi_case'),
        ctrl = this;

      this.getPlaceholder = function() {
        var selectedType = 'Case';
        return ts('Select %1', {1: afGui.getEntity(selectedType).label});
      };

      // Initialize or rebuild form field
      this.$onChanges = function(changes) {
        if (changes.selectedType) {
          let entityType = 'Case';
          if (!ctrl.relatedCaseField || ctrl.relatedCaseField.fk_entity !== entityType) {
            // Replacing the variable with a new object will trigger the afGuiFieldValue to refresh
            ctrl.relatedCaseField = {
              input_type: 'EntityRef',
              data_type: 'Integer',
              fk_entity: entityType,
            };
          }
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
