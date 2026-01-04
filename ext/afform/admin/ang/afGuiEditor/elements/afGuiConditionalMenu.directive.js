(function(angular, $, _) {
  angular.module('afGuiEditor').directive('afGuiConditionalMenu', function() {
    return {
      restrict: 'A',
      templateUrl: '~/afGuiEditor/elements/afGuiConditionalMenu.html',
      require: {
        editor: '^^afGuiEditor'
      },
      bindToController: {
        node: '<afGuiConditionalMenu'
      },
      controller: function($scope) {
        const ts = CRM.ts('org.civicrm.afform_admin'),
          ctrl = this;
      }
    };
  });
})(angular, CRM.$, CRM._);
