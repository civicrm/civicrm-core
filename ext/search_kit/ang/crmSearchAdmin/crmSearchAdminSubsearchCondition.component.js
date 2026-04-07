(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminSubsearchCondition', {
    bindings: {
      filter: '<',
      field: '<',
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminSubsearchCondition.html',
    controller: function ($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.$onInit = () => {
        // If both this.filter.parent_field or this.filter.value exist, delete one; preferably the one that's null
        if ('parent_field' in this.filter && 'value' in this.filter) {
          if (this.filter.value === null || this.filter.parent_field !== null) {
            delete this.filter.value;
          } else {
            delete this.filter.parent_field;
          }
        }
        // Ensure either this.filter.parent_field or this.filter.value exists
        if (!('parent_field' in this.filter) && !('value' in this.filter)) {
          this.filter.parent_field = null;
        }
        this.inputMode = 'parent_field' in this.filter ? 'field' : 'value';
      };

      this.changeInputMode = () => {
        if (this.inputMode === 'value') {
          delete this.filter.parent_field;
          this.filter.value = null;
        } else {
          delete this.filter.value;
          this.filter.parent_field = null;
        }
      };

      this.fieldsForFilter = () => ({
        results: this.crmSearchAdmin.getAllFields('', ['Field', 'Custom', 'Extra']),
      });

    }
  });

})(angular, CRM.$, CRM._);
