(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('crmSearchAdminDisplay', {
    bindings: {
      savedSearch: '<',
      display: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    template: function() {
      // Dynamic template generates switch condition for each display type
      var html =
        '<div ng-include="\'~/crmSearchAdmin/crmSearchAdminDisplay.html\'"></div>\n' +
        '<div ng-switch="$ctrl.display.type">\n';
      _.each(CRM.crmSearchAdmin.displayTypes, function(type) {
        html +=
          '<div ng-switch-when="' + type.id + '">\n' +
          '  <search-admin-display-' + type.id + ' api-entity="$ctrl.savedSearch.api_entity" api-params="$ctrl.savedSearch.api_params" display="$ctrl.display"></search-admin-display-' + type.id + '>\n' +
          '  <hr>\n' +
          '  <button type="button" class="btn btn-{{ !$ctrl.stale ? \'success\' : $ctrl.preview ? \'warning\' : \'primary\' }}" ng-click="$ctrl.previewDisplay()" ng-disabled="!$ctrl.stale">\n' +
          '  <i class="crm-i ' + type.icon + '"></i>' +
          '  {{ $ctrl.preview && $ctrl.stale ? ts("Refresh") : ts("Preview") }}\n' +
          '  </button>\n' +
          '  <hr>\n' +
          '  <div ng-if="$ctrl.preview">\n' +
          '    <' + type.name + ' api-entity="{{:: $ctrl.savedSearch.api_entity }}" search="$ctrl.savedSearch" display="$ctrl.display" settings="$ctrl.display.settings"></' + type.name + '>\n' +
          '  </div>\n' +
          '</div>\n';
      });
      html += '</div>';
      return html;
    },
    controller: function($scope, $timeout, searchMeta) {
      var ts = $scope.ts = CRM.ts(),
        ctrl = this;

      this.preview = this.stale = false;

      this.sortableOptions = {
        connectWith: '.crm-search-admin-edit-columns',
        containment: '.crm-search-admin-edit-columns-wrapper'
      };

      this.removeCol = function(index) {
        ctrl.hiddenColumns.push(ctrl.display.settings.columns[index]);
        ctrl.display.settings.columns.splice(index, 1);
      };

      this.restoreCol = function(index) {
        ctrl.display.settings.columns.push(ctrl.hiddenColumns[index]);
        ctrl.hiddenColumns.splice(index, 1);
      };

      this.getExprFromSelect = function(key) {
        var match;
        _.each(ctrl.savedSearch.api_params.select, function(expr) {
          var parts = expr.split(' AS ');
          if (_.includes(parts, key)) {
            match = parts[0];
            return false;
          }
        });
        return match;
      };

      this.getFieldLabel = function(key) {
        var expr = ctrl.getExprFromSelect(key);
        return searchMeta.getDefaultLabel(expr);
      };

      function fieldToColumn(fieldExpr) {
        var info = searchMeta.parseExpr(fieldExpr);
        return {
          key: info.alias,
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
          ctrl.hiddenColumns = [];
        } else {
          var activeColumns = _.collect(ctrl.display.settings.columns, 'key'),
            selectAliases = _.map(ctrl.savedSearch.api_params.select, function(select) {
              return _.last(select.split(' AS '));
            });
          ctrl.hiddenColumns = _.transform(ctrl.savedSearch.api_params.select, function(hiddenColumns, fieldExpr) {
            var key = _.last(fieldExpr.split(' AS '));
            if (!_.includes(activeColumns, key)) {
              hiddenColumns.push(fieldToColumn(fieldExpr));
            }
          });
          _.eachRight(activeColumns, function(key, index) {
            if (!_.includes(selectAliases, key)) {
              ctrl.display.settings.columns.splice(index, 1);
            }
          });
        }
      };

      this.previewDisplay = function() {
        ctrl.preview = !ctrl.preview;
        ctrl.stale = false;
        if (!ctrl.preview) {
          $timeout(function() {
            ctrl.preview = true;
          }, 100);
        }
      };

      this.fieldsForSort = function() {
        function disabledIf(key) {
          return _.findIndex(ctrl.display.settings.sort, [key]) >= 0;
        }
        return {
          results: [{
            text: ts('Columns'),
            children: ctrl.crmSearchAdmin.getSelectFields(disabledIf)
          }].concat(ctrl.crmSearchAdmin.getAllFields('', disabledIf))
        };
      };

      // Generic function to add to a setting array if the item is not already there
      this.pushSetting = function(name, value) {
        ctrl.display.settings[name] = ctrl.display.settings[name] || [];
        if (_.findIndex(ctrl.display.settings[name], value) < 0) {
          ctrl.display.settings[name].push(value);
        }
      };

      $scope.$watch('$ctrl.display.settings', function() {
        ctrl.stale = true;
      }, true);
    }
  });

})(angular, CRM.$, CRM._);
