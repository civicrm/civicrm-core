(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminDisplay', {
    bindings: {
      savedSearch: '<',
      display: '<'
    },
    template: function() {
      // Dynamic template generates switch condition for each display type
      var html =
        '<div ng-include="\'~/crmSearchAdmin/crmSearchAdminDisplay.html\'"></div>\n' +
        '<div ng-switch="$ctrl.display.type">\n';
      _.each(CRM.crmSearchAdmin.displayTypes, function(type) {
        html +=
          '<div ng-switch-when="' + type.name + '">\n' +
          '  <search-admin-display-' + type.name + ' api-entity="$ctrl.savedSearch.api_entity" api-params="$ctrl.savedSearch.api_params" display="$ctrl.display"></search-admin-display-' + type.name + '>\n' +
          '  <hr>\n' +
          '  <button type="button" class="btn btn-{{ !$ctrl.stale ? \'success\' : $ctrl.preview ? \'warning\' : \'primary\' }}" ng-click="$ctrl.previewDisplay()" ng-disabled="!$ctrl.stale">\n' +
          '  <i class="crm-i ' + type.icon + '"></i>' +
          '  {{ $ctrl.preview && $ctrl.stale ? ts("Refresh") : ts("Preview") }}\n' +
          '  </button>\n' +
          '  <hr>\n' +
          '  <div ng-if="$ctrl.preview">\n' +
          '    <crm-search-display-' + type.name + ' api-entity="$ctrl.savedSearch.api_entity" api-params="$ctrl.savedSearch.api_params" settings="$ctrl.display.settings"></crm-search-display-' + type.name + '>\n' +
          '  </div>\n' +
          '</div>\n';
      });
      html += '</div>';
      return html;
    },
    controller: function($scope, $timeout, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      function fieldToColumn(fieldExpr) {
        var info = searchMeta.parseExpr(fieldExpr);
        return {
          expr: fieldExpr,
          label: searchMeta.getDefaultLabel(fieldExpr),
          dataType: (info.fn && info.fn.name === 'COUNT') ? 'Integer' : info.field.data_type
        };
      }

      // Helper function to sort active from hidden columns and initialize each column with defaults
      this.initColumns = function() {
        if (!ctrl.display.settings.columns) {
          ctrl.display.settings.columns = _.transform(ctrl.savedSearch.api_params.select, function(columns, fieldExpr) {
            columns.push(fieldToColumn(fieldExpr));
          });
          return [];
        } else {
          var activeColumns = _.collect(ctrl.display.settings.columns, 'expr'),
            hiddenColumns = _.transform(ctrl.savedSearch.api_params.select, function(hiddenColumns, fieldExpr) {
            if (!_.includes(activeColumns, fieldExpr)) {
              hiddenColumns.push(fieldToColumn(fieldExpr));
            }
          });
          _.each(activeColumns, function(fieldExpr, index) {
            if (!_.includes(ctrl.savedSearch.api_params.select, fieldExpr)) {
              ctrl.display.settings.columns.splice(index, 1);
            }
          });
          return hiddenColumns;
        }
      };

      // Return all possible links to main entity or join entities
      this.getLinks = function() {
        var links = _.cloneDeep(searchMeta.getEntity(ctrl.savedSearch.api_entity).paths || []);
        _.each(ctrl.savedSearch.api_params.join, function(join) {
          var joinName = join[0].split(' AS '),
            joinEntity = searchMeta.getEntity(joinName[0]);
          _.each(joinEntity.paths, function(path) {
            var link = _.cloneDeep(path);
            link.path = link.path.replace(/\[/g, '[' + joinName[1] + '.');
            links.push(link);
          });
        });
        return links;
      };

      this.preview = this.stale = false;

      this.previewDisplay = function() {
        ctrl.preview = !ctrl.preview;
        ctrl.stale = false;
        if (!ctrl.preview) {
          $timeout(function() {
            ctrl.preview = true;
          }, 100);
        }
      };

      $scope.$watch('$ctrl.display.settings', function() {
        ctrl.stale = true;
      }, true);
    }
  });

})(angular, CRM.$, CRM._);
