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
      let html = '<div ng-switch="$ctrl.display.type">\n';
      CRM.crmSearchAdmin.displayTypes.forEach(function(type) {
        html +=
          '<div ng-switch-when="' + type.id + '">\n' +
          '  <div class="help-block"><i class="crm-i ' + type.icon + '" role="img" aria-hidden="true"></i> ' + _.escape(type.description) + '</div>' +
          '  <search-admin-display-' + type.id + ' api-entity="$ctrl.savedSearch.api_entity" api-params="$ctrl.savedSearch.api_params" display="$ctrl.display"></search-admin-display-' + type.id + '>\n' +
          '  <hr>\n' +
          '  <button type="button" class="btn btn-{{ !$ctrl.stale ? \'success\' : $ctrl.preview ? \'warning\' : \'primary\' }}" ng-click="$ctrl.previewDisplay()" ng-disabled="!$ctrl.stale">\n' +
          '  <i class="crm-i ' + type.icon + '" role="img" aria-hidden="true"></i>' +
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
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      let initDefaults;

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
            size: 'btn-xs',
            links: []
          }
        },
        menu: {
          label: ts('Menu'),
          icon: 'fa-bars',
          defaults: {
            text: '',
            style: 'default',
            size: 'btn-xs',
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

      this.dateFormats = CRM.crmSearchAdmin.dateFormats;
      this.numberAttributes = CRM.crmSearchAdmin.numberAttributes;

      // Drag-n-drop settings for reordering columns
      this.sortableOptions = {
        connectWith: '.crm-search-admin-edit-columns',
        containment: '.crm-search-admin-edit-columns-wrapper',
        cancel: 'input,textarea,button,select,option,a,label'
      };

      this.styles = CRM.crmSearchAdmin.styles;

      function selectToKey(selectExpr) {
        return selectExpr.split(' AS ').slice(-1)[0];
      }

      this.getMainEntity = function() {
        return searchMeta.getEntity(this.savedSearch.api_entity);
      };

      this.addCol = function(type) {
        const col = _.cloneDeep(this.colTypes[type].defaults);
        col.type = type;
        if (this.display.type === 'table') {
          col.alignment = 'text-right';
        }
        ctrl.display.settings.columns.push(col);
      };

      this.removeCol = function(index) {
        ctrl.display.settings.columns.splice(index, 1);
      };

      this.getColumnIndex = function(key) {
        key = selectToKey(key);
        return ctrl.display.settings.columns.findIndex(col => key === col.key);
      };

      this.columnExists = function(key) {
        return ctrl.getColumnIndex(key) > -1;
      };

      this.toggleColumn = function(key) {
        let index = ctrl.getColumnIndex(key);
        if (index > -1) {
          ctrl.removeCol(index);
        } else {
          ctrl.display.settings.columns.push(searchMeta.fieldToColumn(key, initDefaults));
        }
      };

      this.getDataType = function(key) {
        const expr = ctrl.getExprFromSelect(key);
        const info = searchMeta.parseExpr(expr);
        const field = (_.findWhere(info.args, {type: 'field'}) || {}).field || {};
        return (info.fn && info.fn.data_type) || field.data_type;
      };

      this.isDate = function(key) {
        return ['Date', 'Timestamp'].includes(this.getDataType(key));
      };

      this.getExprFromSelect = function(key) {
        let fieldKey = key.split(':')[0];
        let match = ctrl.savedSearch.api_params.select.find((expr) => {
          let parts = expr.split(' AS ');
          return (parts[1] === fieldKey || parts[0].split(':')[0] === fieldKey);
        });
        return match ? match.split(' AS ')[0] : null;
      };

      this.getFieldLabel = function(key) {
        const expr = ctrl.getExprFromSelect(selectToKey(key));
        return searchMeta.getDefaultLabel(expr, ctrl.savedSearch);
      };

      this.getColLabel = function(col) {
        if (col.type === 'field' || col.type === 'image' || col.type === 'html') {
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

      this.toggleHtml = function(col) {
        if (col.type === 'html') {
          col.type = 'field';
        } else {
          delete col.editable;
          delete col.link;
          delete col.icons;
          col.type = 'html';
        }
      };

      this.getSuffixOptions = function(col) {
        let expr = ctrl.getExprFromSelect(col.key);
        return ctrl.crmSearchAdmin.getSuffixOptions(expr);
      };

      function getSetSuffix(index, val) {
        let col = ctrl.display.settings.columns[index];
        if (arguments.length > 1) {
          col.key = col.key.split(':')[0] + (val ? ':' + val : '');
        }
        return col.key.split(':')[1] || '';
      }

      // Provides getter/setter for the pseudoconstant suffix selector
      this.getSetSuffix = function(index) {
        return _.wrap(index, getSetSuffix);
      };

      this.canBeImage = function(col) {
        const expr = ctrl.getExprFromSelect(col.key),
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
        const expr = ctrl.getExprFromSelect(col.key),
          info = searchMeta.parseExpr(expr);
        return !col.rewrite && !col.link && !info.fn && info.args[0] && info.args[0].field && !info.args[0].field.readonly;
      };

      // Checks if a column contains a sortable value
      // Must be a real sql expression (not a pseudo-field like `result_row_num`)
      this.canBeSortable = function(col) {
        // Column-header sorting is incompatible with draggable sorting
        if (!col.key || ctrl.display.settings.draggable) {
          return false;
        }
        const expr = ctrl.getExprFromSelect(col.key),
          info = searchMeta.parseExpr(expr),
          arg = (info && info.args && _.findWhere(info.args, {type: 'field'})) || {};
        return arg.field && arg.field.type !== 'Pseudo';
      };

      // Aggregate functions (COUNT, AVG, MAX) cannot autogenerate links, except for GROUP_CONCAT
      // which gets special treatment in APIv4 to convert it to an array.
      function canUseLinks(colKey) {
        const expr = ctrl.getExprFromSelect(colKey),
          info = searchMeta.parseExpr(expr);
        return !info.fn || info.fn.category !== 'aggregate' || info.fn.name === 'GROUP_CONCAT';
      }

      const LINK_PROPS = ['path', 'entity', 'action', 'join', 'target', 'task'];

      this.toggleLink = function(column) {
        if (column.link) {
          ctrl.onChangeLink(column, {});
        } else {
          delete column.editable;
          const defaultLink = ctrl.getLinks(column.key)[0];
          ctrl.onChangeLink(column, defaultLink || {path: 'civicrm/'});
        }
      };

      this.onChangeLink = function(column, afterLink) {
        column.link = column.link || {};
        const beforeLink = column.link.action && _.findWhere(ctrl.getLinks(column.key), {action: column.link.action});
        if (!afterLink.action && !afterLink.path && !afterLink.task) {
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
        LINK_PROPS.forEach((prop) => {
          column.link[prop] = afterLink[prop] || '';
        });
      };

      this.getLinks = function(columnKey) {
        if (!ctrl.links) {
          ctrl.links = {
            '*': ctrl.crmSearchAdmin.buildLinks(true),
            '0': []
          };
          ctrl.links[''] = _.filter(ctrl.links['*'], {join: ''});
          searchMeta.getSearchTasks(ctrl.savedSearch.api_entity).then(function(tasks) {
            tasks.forEach(function (task) {
              if (task.number === '> 0' || task.number === '=== 1') {
                const link = {
                  text: task.title,
                  icon: task.icon,
                  task: task.name,
                  entity: task.entity,
                  target: 'crm-popup',
                  join: '',
                  style: task.name === 'delete' ? 'danger' : 'default'
                };
                ctrl.links['*'].push(link);
                ctrl.links[''].push(link);
              }
            });
          });
        }
        if (!columnKey) {
          return ctrl.links['*'];
        }
        if (!canUseLinks(columnKey)) {
          return ctrl.links['0'];
        }
        const expr = ctrl.getExprFromSelect(columnKey),
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
        initDefaults = defaults;
        if (!ctrl.display.settings.columns) {
          ctrl.display.settings.columns = _.transform(ctrl.savedSearch.api_params.select, function(columns, fieldExpr) {
            columns.push(searchMeta.fieldToColumn(fieldExpr, defaults));
          });
        } else {
          let activeColumns = ctrl.display.settings.columns.map(col => col.key);
          // Delete any column that is no longer in the search
          activeColumns.reverse().forEach((key, index) => {
            if (key && !ctrl.getExprFromSelect(key)) {
              ctrl.removeCol(activeColumns.length - 1 - index);
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

      this.getDefaultLimit = function() {
        return CRM.crmSearchAdmin.defaultPagerSize;
      };

      this.getDefaultSort = function() {
        const sort = [];
        if (this.getMainEntity().order_by) {
          sort.push([this.getMainEntity().order_by, 'ASC']);
        }
        return sort;
      };

      this.fieldsForSort = function() {
        function disabledIf(key) {
          return ctrl.display.settings.sort.findIndex(sort => sort[0] === key) >= 0;
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
          ].concat(ctrl.crmSearchAdmin.getAllFields('', ['Field', 'Custom', 'Extra'], disabledIf))
        };
      };

      this.fieldsForSearch = function() {
        function disabledIf(key) {
          return ctrl.display.settings.searchFields.findIndex(field => field === key) >= 0;
        }
        return {
          results: ctrl.crmSearchAdmin.getAllFields('', ['Field', 'Custom', 'Extra'], disabledIf),
        };
      };

      this.toggleDraggable = function() {
        if (this.display.settings.draggable) {
          this.display.settings.draggable = false;
        } else {
          this.display.settings.sort = [];
          this.display.settings.draggable = this.getMainEntity().order_by;
        }
      };

      // Generic function to add to a setting array if the item is not already there
      this.pushSetting = function(name, value) {
        ctrl.display.settings[name] = ctrl.display.settings[name] || [];
        if (!ctrl.display.settings[name].includes(value)) {
          ctrl.display.settings[name].push(value);
        }
      };

      // Add or remove an item from an array
      this.toggle = function(collection, item) {
        const index = collection.indexOf(item);
        if (index > -1) {
          collection.splice(index, 1);
        } else {
          collection.push(item);
        }
      };

      this.tableClasses = [
        {name: 'table', label: ts('Row Borders')},
        {name: 'table-bordered', label: ts('Column Borders')},
        {name: 'table-striped', label: ts('Even/Odd Stripes')},
        {name: 'crm-sticky-header', label: ts('Sticky Header')}
      ];

      $scope.$watch('$ctrl.display.settings', function() {
        ctrl.stale = true;
      }, true);
    }
  });

})(angular, CRM.$, CRM._);
