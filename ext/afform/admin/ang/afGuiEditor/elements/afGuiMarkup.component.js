// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  let richtextId = 0;

  angular.module('afGuiEditor').component('afGuiMarkup', {
    templateUrl: '~/afGuiEditor/elements/afGuiMarkup.html',
    bindings: {
      node: '=',
      deleteThis: '&'
    },
    require: {
      editor: '^^afGuiEditor',
    },
    controller: function($element, $scope) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin');

      this.$onInit = () => {

        // When creating a new markup container, go straight to edit mode
        if (!this.node['#markup']) {
          this.edit();
        }
      };

      this.edit = () => $element[0].querySelector('civi-rich-text-input').openEditor();

    }
  });

})(angular, CRM.$, CRM._);
