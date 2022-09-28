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

      this.setValue = function(val) {
        ctrl.link  = ctrl.link  || {};
        var link = ctrl.getLink(val),
          oldVal = ctrl.link.path;
        ctrl.link.path = val;
        if (!link) {
          $timeout(function () {
            $('input[type=text]', $element).focus();
          });
        }
        ctrl.onChange({before: oldVal, after: val});
      };

      this.getLink = function(path) {
        return _.findWhere(ctrl.links, {path: path});
      };

    }
  });

})(angular, CRM.$, CRM._);
