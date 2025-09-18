// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiSearchDisplay', {
    templateUrl: '~/afGuiEditor/elements/afGuiSearchDisplay.html',
    bindings: {
      node: '='
    },
    controller: function($scope, afGui) {
      var ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.$onInit = function() {
        ctrl.display = afGui.getSearchDisplay(ctrl.node['search-name'], ctrl.node['display-name']);
        if (checkEditAccess(ctrl.display)) {
          ctrl.editUrl = CRM.url('civicrm/admin/search#/edit/' + ctrl.display.saved_search_id);
        }
      };

      function checkEditAccess(display) {
        if (CRM.checkPerm('all CiviCRM permissions and ACLs')) {
          return true;
        }
        // Only super-admins can edit displays with acl_bypass
        if (display.acl_bypass) {
          return false;
        }
        if (CRM.checkPerm('administer search_kit')) {
          return true;
        }
        // Check manage-own permission
        return (CRM.checkPerm('manage own search_kit') && (display['saved_search_id.created_id'] === CRM.config.cid));
      }

    }
  });

})(angular, CRM.$, CRM._);
