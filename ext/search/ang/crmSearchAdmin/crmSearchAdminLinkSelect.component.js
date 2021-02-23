(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminLinkSelect', {
    bindings: {
      column: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      crmSearchAdmin: '^^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/crmSearchAdminLinkSelect.html',
    controller: function ($scope, $element, $timeout, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      // Return all possible links to main entity or join entities
      function getLinks() {
        // Links to main entity
        var links = _.cloneDeep(searchMeta.getEntity(ctrl.apiEntity).paths || []);
        // Links to explicitly joined entities
        _.each(ctrl.apiParams.join, function(join) {
          var joinName = join[0].split(' AS '),
            joinEntity = searchMeta.getEntity(joinName[0]);
          _.each(joinEntity.paths, function(path) {
            var link = _.cloneDeep(path);
            link.path = link.path.replace(/\[/g, '[' + joinName[1] + '.');
            links.push(link);
          });
        });
        // Links to implicit joins
        _.each(ctrl.crmSearchAdmin.savedSearch.api_params.select, function(fieldName) {
          if (!_.includes(fieldName, ' AS ')) {
            var info = searchMeta.parseExpr(fieldName);
            if (info.field && !info.suffix && !info.fn && (info.field.fk_entity || info.field.entity !== info.field.baseEntity)) {
              var idField = info.field.fk_entity ? fieldName : fieldName.substr(0, fieldName.lastIndexOf('.')) + '_id';
              if (!ctrl.crmSearchAdmin.canAggregate(idField)) {
                var joinEntity = searchMeta.getEntity(info.field.fk_entity || info.field.entity);
                _.each(joinEntity.paths, function(path) {
                  var link = _.cloneDeep(path);
                  link.path = link.path.replace(/\[id/g, '[' + idField);
                  links.push(link);
                });
              }
            }
          }
        });
        return links;
      }

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
