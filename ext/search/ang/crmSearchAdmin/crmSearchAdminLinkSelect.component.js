(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminLinkSelect', {
    bindings: {
      column: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminLinkSelect.html',
    controller: function ($scope, $element, $timeout, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      // Return all possible links to main entity or join entities
      function getLinks() {
        var links = _.cloneDeep(searchMeta.getEntity(ctrl.apiEntity).paths || []);
        _.each(ctrl.apiParams.join, function(join) {
          var joinName = join[0].split(' AS '),
            joinEntity = searchMeta.getEntity(joinName[0]);
          _.each(joinEntity.paths, function(path) {
            var link = _.cloneDeep(path);
            link.path = link.path.replace(/\[/g, '[' + joinName[1] + '.');
            links.push(link);
          });
        });
        return links;
      }

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
        this.links = getLinks();

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
