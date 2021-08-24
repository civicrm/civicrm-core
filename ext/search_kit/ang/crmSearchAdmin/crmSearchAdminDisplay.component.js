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
      var ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.isSuperAdmin = CRM.checkPerm('all CiviCRM permissions and ACLs');
      this.aclBypassHelp = ts('Only users with "all CiviCRM permissions and ACLs" can disable permission checks.');

      this.preview = this.stale = false;

      this.colTypes = {
        links: {
          label: ts('Links'),
          icon: 'fa-link',
          defaults: {
            links: []
          }
        },
        buttons: {
          label: ts('Buttons'),
          icon: 'fa-square-o',
          defaults: {
            size: 'btn-sm',
            links: []
          }
        },
        menu: {
          label: ts('Menu'),
          icon: 'fa-bars',
          defaults: {
            text: ts('Actions'),
            style: 'default',
            size: 'btn-sm',
            icon: 'fa-bars',
            links: []
          }
        },
      };

      // Drag-n-drop settings for reordering columns
      this.sortableOptions = {
        connectWith: '.crm-search-admin-edit-columns',
        containment: '.crm-search-admin-edit-columns-wrapper',
        cancel: 'input,textarea,button,select,option,a,label'
      };

      this.styles = CRM.crmSearchAdmin.styles;

      this.addCol = function(type) {
        var col = _.cloneDeep(this.colTypes[type].defaults);
        col.type = type;
        if (this.display.type === 'table') {
          col.alignment = 'text-right';
        }
        ctrl.display.settings.columns.push(col);
      };

      this.removeCol = function(index) {
        if (ctrl.display.settings.columns[index].type === 'field') {
          ctrl.hiddenColumns.push(ctrl.display.settings.columns[index]);
        }
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

      this.getColLabel = function(col) {
        if (col.type === 'field') {
          return ctrl.getFieldLabel(col.key);
        }
        return ctrl.colTypes[col.type].label;
      };

      this.toggleRewrite = function(col) {
        if (col.rewrite) {
          col.rewrite = '';
        } else {
          col.rewrite = '[' + col.key + ']';
          delete col.editable;
        }
      };

      this.toggleImage = function(col) {
        if (col.imageSrc) {
          col.imageSrc = '';
          col.imageAlt = '';
        } else {
          // this means custom field of type file
          if (col.dataType == 'Integer') {
            // TODO: need to switch to civicrm generated urls with checksum
            col.imageSrc = '[' + col.key + ']';
          } else {
            // contact image and everything else
            col.imageSrc = '[' + col.key + ']';
          }
          col.imageAlt = '[' + col.key + ']';
          delete col.editable;
        }
      };

      this.toggleEditable = function(col) {
        if (col.editable) {
          delete col.editable;
          return;
        }

        var info = searchMeta.parseExpr(col.key),
          value = col.key.split(':')[0];
        // If field is an implicit join, use the original fk field
        if (info.field.name !== info.field.fieldName) {
          value = value.substr(0, value.lastIndexOf('.'));
          info = searchMeta.parseExpr(value);
        }
        col.editable = {
          entity: info.field.baseEntity,
          options: !!info.field.options,
          serialize: !!info.field.serialize,
          fk_entity: info.field.fk_entity,
          id: info.prefix + 'id',
          name: info.field.name,
          value: value
        };
      };

      this.isEditable = function(col) {
        var expr = ctrl.getExprFromSelect(col.key),
          info = searchMeta.parseExpr(expr);
        return !col.imageSrc && !col.rewrite && !col.link && !info.fn && info.field && !info.field.readonly;
      };

      this.toggleLink = function(column) {
        if (column.link) {
          ctrl.onChangeLink(column, column.link.path, '');
        } else {
          var defaultLink = ctrl.getLinks(column.key)[0];
          column.link = {path: defaultLink ? defaultLink.path : 'civicrm/'};
          ctrl.onChangeLink(column, null, column.link.path);
        }
      };

      this.onChangeLink = function(column, before, after) {
        var beforeLink = before && _.findWhere(ctrl.getLinks(), {path: before}),
          afterLink = after && _.findWhere(ctrl.getLinks(), {path: after});
        if (!after) {
          if (beforeLink && column.title === beforeLink.title) {
            delete column.title;
          }
          delete column.link;
        } else if (afterLink && ((!column.title && !before) || (beforeLink && beforeLink.title === column.title))) {
          column.title = afterLink.title;
        } else if (!afterLink && (beforeLink && beforeLink.title === column.title)) {
          delete column.title;
        }
      };

      this.getLinks = function(columnKey) {
        if (!ctrl.links) {
          ctrl.links = {'*': ctrl.crmSearchAdmin.buildLinks()};
        }
        if (!columnKey) {
          return ctrl.links['*'];
        }
        var expr = ctrl.getExprFromSelect(columnKey),
          info = searchMeta.parseExpr(expr),
          joinEntity = searchMeta.getJoinEntity(info);
        if (!ctrl.links[joinEntity]) {
          ctrl.links[joinEntity] = _.filter(ctrl.links['*'], {join: joinEntity});
        }
        return ctrl.links[joinEntity];
      };

      this.pickIcon = function(model, key) {
        searchMeta.pickIcon().then(function(icon) {
          model[key] = icon;
        });
      };

      // Helper function to sort active from hidden columns and initialize each column with defaults
      this.initColumns = function(defaults) {
        if (!ctrl.display.settings.columns) {
          ctrl.display.settings.columns = _.transform(ctrl.savedSearch.api_params.select, function(columns, fieldExpr) {
            columns.push(searchMeta.fieldToColumn(fieldExpr, defaults));
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
              hiddenColumns.push(searchMeta.fieldToColumn(fieldExpr, defaults));
            }
          });
          _.eachRight(activeColumns, function(key, index) {
            if (key && !_.includes(selectAliases, key)) {
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
          }].concat(ctrl.crmSearchAdmin.getAllFields('', ['Field', 'Custom'], disabledIf))
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
