// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // For configuring autofill by related contact
  angular.module('afGuiEditor').component('autofillRelationshipBehaviorForm', {
    templateUrl: '~/afGuiEditor/behaviors/autofillRelationshipBehaviorForm.html',
    bindings: {
      entity: '<',
      selectedType: '<',
      relTypes: '<'
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.getPlaceholder = function() {
        var selectedType = _.find(ctrl.relTypes, {name: ctrl.selectedType}).contact_type || 'Contact';
        return ts('Select %1', {1: afGui.getEntity(selectedType).label});
      };

      // Initialize or rebuild form field
      this.$onChanges = function(changes) {
        if (changes.selectedType) {
          var selectedType = _.find(ctrl.relTypes, {name: ctrl.selectedType});
          if (!ctrl.relatedContactField || ctrl.relatedContactField.input_attrs.filter.contact_type !== selectedType.contact_type) {
            // Replacing the variable with a new object will trigger the afGuiFieldValue to refresh
            ctrl.relatedContactField = {
              input_type: 'EntityRef',
              data_type: 'Integer',
              fk_entity: 'Contact',
              input_attrs: {filter: {}}
            };
            if (selectedType.contact_type) {
              ctrl.relatedContactField.input_attrs.filter.contact_type = selectedType.contact_type;
            }
          }
        }
      };
    }
  });

})(angular, CRM.$, CRM._);
