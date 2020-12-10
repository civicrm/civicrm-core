(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchActions').directive('saveSmartGroup', function() {
    return {
      bindToController: {
        load: '<',
        entity: '<',
        params: '<'
      },
      restrict: 'A',
      controller: function ($scope, $element, dialogService) {
        var ts = $scope.ts = CRM.ts(),
          ctrl = this;

        $scope.saveGroup = function () {
          var model = {
            title: '',
            description: '',
            visibility: 'User and User Admin Only',
            group_type: [],
            id: ctrl.load ? ctrl.load.id : null,
            api_entity: ctrl.entity,
            api_params: _.cloneDeep(angular.extend({}, ctrl.params, {version: 4}))
          };
          delete model.api_params.orderBy;
          if (ctrl.load && ctrl.load.api_params && ctrl.load.api_params.select && ctrl.load.api_params.select[0]) {
            model.api_params.select.unshift(ctrl.load.api_params.select[0]);
          }
          var options = CRM.utils.adjustDialogDefaults({
            autoOpen: false,
            title: ts('Save smart group')
          });
          dialogService.open('saveSearchDialog', '~/crmSearchActions/saveSmartGroup.html', model, options);
        };
      }
    };
  });

})(angular, CRM.$, CRM._);
