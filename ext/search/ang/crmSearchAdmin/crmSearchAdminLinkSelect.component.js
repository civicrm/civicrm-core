(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminLinkSelect', {
    bindings: {
      column: '<',
      apiEntity: '<',
      apiParams: '<',
      links: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminLinkSelect.html',
    controller: function ($scope, $element, $timeout) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.setValue = function(val) {
        var link = ctrl.getLink(val),
          oldLink = ctrl.getLink(ctrl.column.link);
        if (link) {
          ctrl.column.link = link.path;
          ctrl.column.title = link.title;
        } else {
          if (val === 'civicrm/') {
            ctrl.column.link = val;
            $timeout(function () {
              $('input[type=text]', $element).focus();
            });
          } else {
            ctrl.column.link = '';
          }
          if (oldLink && ctrl.column.title === oldLink.title) {
            ctrl.column.title = '';
          }
        }
      };

      function onChange() {
        var val = $('select', $element).val();
        if (val !== ctrl.column.link) {
          ctrl.setValue(val);
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
