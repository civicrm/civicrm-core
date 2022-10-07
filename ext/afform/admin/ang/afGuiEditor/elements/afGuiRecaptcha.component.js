// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiRecaptcha', {
    templateUrl: '~/afGuiEditor/elements/afGuiRecaptcha.html',
    bindings: {
      node: '=',
      deleteThis: '&'
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;
    }
  });

})(angular, CRM.$, CRM._);
