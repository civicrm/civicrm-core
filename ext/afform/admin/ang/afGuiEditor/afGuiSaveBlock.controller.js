// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').controller('afGuiSaveBlock', function($scope, crmApi4, dialogService) {
    const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
      model = $scope.model,
      original = $scope.original = {
        title: model.title,
        name: model.name
      };
    if (model.name) {
      $scope.$watch('model.name', function(val, oldVal) {
        if (!val && model.title === original.title) {
          model.title += ' ' + ts('(copy)');
        }
        else if (val === original.name && val !== oldVal) {
          model.title = original.title;
        }
      });
    }
    $scope.cancel = function() {
      dialogService.cancel('saveBlockDialog');
    };
    $scope.save = function() {
      $('.ui-dialog:visible').block();
      crmApi4('Afform', 'save', {formatWhitespace: true, records: [JSON.parse(angular.toJson(model))]})
        .then(function(result) {
          dialogService.close('saveBlockDialog', result[0]);
        });
    };
  });

})(angular, CRM.$, CRM._);
