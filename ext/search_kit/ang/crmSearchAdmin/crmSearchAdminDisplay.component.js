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

      // Extra (non-field) colum types
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
        include: {
          label: ts('Custom Code'),
          icon: 'fa-code',
          defaults: {
            path: ''
          }
        }
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
        if (col.type === 'field' || col.type === 'image') {
          return ctrl.getFieldLabel(col.key);
        }
        return ctrl.colTypes[col.type].label;
      };

      this.toggleEmptyVal = function(col) {
        if (col.empty_value) {
          delete col.empty_value;
        } else {
          col.empty_value = ts('None');
        }
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
        if (col.type === 'image') {
          delete col.image;
          col.type = 'field';
        } else {
          col.image = {
            alt: this.getColLabel(col)
          };
          delete col.editable;
          col.type = 'image';
        }
      };

      this.canBeImage = function(col) {
        var expr = ctrl.getExprFromSelect(col.key),
          info = searchMeta.parseExpr(expr);
        return info.args[0] && info.args[0].field && info.args[0].field.input_type === 'File';
      };

      this.toggleEditable = function(col) {
        if (col.editable) {
          delete col.editable;
        } else {
          col.editable = true;
        }
      };

      this.canBeEditable = function(col) {
        var expr = ctrl.getExprFromSelect(col.key),
          info = searchMeta.parseExpr(expr);
        return !col.rewrite && !col.link && !info.fn && info.args[0] && info.args[0].field && !info.args[0].field.readonly;
      };

      // Checks if a column contains a sortable value
      // Must be a real sql expression (not a pseudo-field like `result_row_num`)
      this.canBeSortable = function(col) {
        // Column-header sorting is incompatible with draggable sorting
        if (ctrl.display.settings.draggable) {
          return false;
        }
        var expr = ctrl.getExprFromSelect(col.key),
          info = searchMeta.parseExpr(expr),
          arg = (info && info.args && _.findWhere(info.args, {type: 'field'})) || {};
        return arg.field && arg.field.type !== 'Pseudo';
      };

      // Aggregate functions (COUNT, AVG, MAX) cannot display as links, except for GROUP_CONCAT
      // which gets special treatment in APIv4 to convert it to an array.
      this.canBeLink = function(col) {
        var expr = ctrl.getExprFromSelect(col.key),
          info = searchMeta.parseExpr(expr);
        return !info.fn || info.fn.category !== 'aggregate' || info.fn.name === 'GROUP_CONCAT';
      };

      var linkProps = ['path', 'entity', 'action', 'join', 'target'];

      this.toggleLink = function(column) {
        if (column.link) {
          ctrl.onChangeLink(column, {});
        } else {
          delete column.editable;
          var defaultLink = ctrl.getLinks(column.key)[0];
          ctrl.onChangeLink(column, defaultLink || {path: 'civicrm/'});
        }
      };

      this.onChangeLink = function(column, afterLink) {
        column.link = column.link || {};
        var beforeLink = column.link.action && _.findWhere(ctrl.getLinks(column.key), {action: column.link.action});
        if (!afterLink.action && !afterLink.path) {
          if (beforeLink && beforeLink.text === column.title) {
            delete column.title;
          }
          delete column.link;
          return;
        }
        if (afterLink.text && ((!column.title && !beforeLink) || (beforeLink && beforeLink.text === column.title))) {
          column.title = afterLink.text;
        } else if (!afterLink.text && (beforeLink && beforeLink.text === column.title)) {
          delete column.title;
        }
        _.each(linkProps, function(prop) {
          column.link[prop] = afterLink[prop] || '';
        });
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
          results: [
            {
              text: ts('Random'),
              icon: 'crm-i fa-random',
              id: 'RAND()',
              disabled: disabledIf('RAND()')
            },
            {
              text: ts('Columns'),
              children: ctrl.crmSearchAdmin.getSelectFields(disabledIf)
            }
          ].concat(ctrl.crmSearchAdmin.getAllFields('', ['Field', 'Custom'], disabledIf))
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
