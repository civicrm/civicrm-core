(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminLinkSelect', {
    bindings: {
      column: '<',
      links: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminLinkSelect.html',
    controller: function ($scope, $element, $timeout) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      function onChange() {
        var val = $('select', $element).val();
        if (val !== ctrl.column.link) {
          var link = ctrl.getLink(val);
          if (link) {
            ctrl.column.link = link.path;
            ctrl.column.title = link.title;
          } else if (val === 'civicrm/') {
            ctrl.column.link = val;
            $timeout(function() {
              $('input', $element).focus();
            });
          } else {
            ctrl.column.link = '';
            ctrl.column.title = '';
          }
        }
      }

      this.$onInit = function() {
        $('select', $element).on('change', function() {
          $scope.$apply(onChange);
        });
      };

      this.getLink = function(path) {
        return _.findWhere(ctrl.links, {path: path});
      };

    }
  });

})(angular, CRM.$, CRM._);
