(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminTokenSelect', {
    bindings: {
      model: '<',
      field: '@',
      suffix: '@'
    },
    require: {
      admin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminTokenSelect.html',
    controller: function ($scope, $element, searchMeta) {
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.insertToken = function(key) {
        ctrl.model[ctrl.field] = (ctrl.model[ctrl.field] || '') + '[' + key + ']';
      };

      this.getTokens = function() {
        var allFields = ctrl.admin.getAllFields(ctrl.suffix || '', ['Field', 'Custom', 'Extra']);
        _.eachRight(ctrl.admin.savedSearch.api_params.select, function(fieldName) {
          allFields.unshift({
            id: fieldName,
            text: searchMeta.getDefaultLabel(fieldName)
          });
        });
        return {
          results: allFields
        };
      };

    }
  });

})(angular, CRM.$, CRM._);
