(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminLinkSelect', {
    bindings: {
      link: '<',
      apiEntity: '<',
      apiParams: '<',
      links: '<',
      onChange: '&'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminLinkSelect.html',
    controller: function ($scope, $element, $timeout) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        $element.on('hidden.bs.dropdown', function() {
          $scope.$apply(function() {
            ctrl.menuOpen = false;
          });
        });
      };

      this.setValue = function(val) {
        if (val.path) {
          $timeout(function () {
            $('input[type=text]', $element).focus();
          });
        }
        ctrl.onChange({newLink: val});
      };

      this.getLink = function() {
        return _.find(ctrl.links, function(link) {
          if (ctrl.link.task && link.task === ctrl.link.task && link.entity === ctrl.link.entity) {
            return true;
          } else if (ctrl.link.action && link.action === ctrl.link.action && link.entity === ctrl.link.entity && link.join == ctrl.link.join) {
            return true;
          }
          return false;
        });
      };

    }
  });

})(angular, CRM.$, CRM._);
