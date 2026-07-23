(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchConditions', {
    bindings: {
      item: '<',
      isToolbar: '<',
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchConditions.html',
    controller: function ($scope, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');

      this.permissions = CRM.crmSearchAdmin.permissions;

      this.permissionOperators = [
        {key: 'CONTAINS', value: ts('Includes')},
        {key: '=', value: ts('Has All')},
        {key: '!=', value: ts('Lacks All')}
      ];

      this.getField = searchMeta.getField;

      this.fields = () => {
        let selectFields = this.crmSearchAdmin.getSelectFields(this.crmSearchAdmin.savedSearch);
        // Use machine names not labels for option matching
        selectFields.forEach((field) => field.id = field.id.replace(':label', ':name'));
        let extraConditions = [{
          text: ts('Current User Permission'),
          id: 'check user permission',
          description: ts('Check permission of logged-in user')
        }];
        if (this.isToolbar) {
          extraConditions.push({
            text: ts('No Results'),
            id: 'no results',
            description: ts('Only show this button when the search has no results')
          });
        }
        return {results: extraConditions.concat(selectFields)};
      };

      this.addCondition = (selection) => {
        this.item.conditions = this.item.conditions || [];
        this.item.conditions.push([selection, '=']);
      };

      this.onChangeCondition = (index) => {
        if (this.item.conditions[index][0]) {
          this.item.conditions[index][1] = '=';
        } else {
          this.item.conditions.splice(index, 1);
        }
      };

    },
  });

})(angular, CRM.$, CRM._);
