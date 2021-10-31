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
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
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
        return _.findWhere(ctrl.links, {action: ctrl.link.action, join: ctrl.link.join, entity: ctrl.link.entity});
      };

    }
  });

})(angular, CRM.$, CRM._);
