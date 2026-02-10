(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayTable', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayTable.html',
    controller: function($scope, searchMeta, formatForSelect2, crmUiHelp) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Display'});


      // Check if array contains item
      this.includes = _.includes;

      this.getColTypes = function() {
        return ctrl.parent.colTypes;
      };

      this.$onInit = function () {
        if (!ctrl.display.settings) {
          ctrl.display.settings = _.extend({}, _.cloneDeep(CRM.crmSearchAdmin.defaultDisplay.settings), {columns: null, pager: {}});
          ctrl.display.settings.sort = ctrl.parent.getDefaultSort();
        }
        // Displays created prior to 5.43 may not have this property
        ctrl.display.settings.classes = ctrl.display.settings.classes || [];
        // Table can be draggable if the main entity is a SortableEntity.
        ctrl.sortableEntity = _.includes(searchMeta.getEntity(ctrl.apiEntity).type, 'SortableEntity');
        ctrl.hierarchicalEntity = _.includes(searchMeta.getEntity(ctrl.apiEntity).type, 'HierarchicalEntity');

        if (ctrl.display.settings.columnMode) {
          // calling the setter seems redundant, but will run initColumns if needed
          this.setColumnMode(ctrl.display.settings.columnMode);
        }
        else {
          // determine the column mode to use:
          // - for new displays is the default is `auto`
          // - EXCEPT if we already have columns defined, this is reloading a display
          // created before columnMode existed => use `custom` to preserve
          // existing behaviour
          if (ctrl.display.settings.columns) {
            this.setColumnMode('custom');
          }
          else {
            this.setColumnMode('auto');
          }
        }
      };

      this.setColumnMode = (value) => {
        // if not using auto columns we need to run initColumns to initialise defaults
        // and populate or validate this.settings.columns
        if (value !== 'auto') {
          this.parent.initColumns({label: true, sortable: true});
        }
        this.display.settings.columnMode = value;
      };

      this.toggleEditableRowMode = function(name, value) {
        ctrl.display.settings.editableRow = ctrl.display.settings.editableRow || {};
        if (arguments.length < 2) {
          value = !ctrl.display.settings.editableRow[name];
        }
        if (value === ctrl.display.settings.editableRow[name]) {
          return;
        }
        ctrl.display.settings.editableRow[name] = value;
        if (!value) {
          delete ctrl.display.settings.editableRow[name];
          if (name === 'create') {
            delete ctrl.display.settings.editableRow.disable;
            delete ctrl.display.settings.editableRow.createLabel;
          }
        }
        else if (name === 'create') {
          ctrl.display.settings.editableRow.createLabel = ts('Add');
        }
        if (name === 'full') {
          delete ctrl.display.settings.editableRow.disable;
        }
        if (name === 'disable') {
          delete ctrl.display.settings.editableRow.full;
        }
        if (angular.equals({}, ctrl.display.settings.editableRow)) {
          delete ctrl.display.settings.editableRow;
        }
      };

      this.toggleTally = function() {
        if (ctrl.display.settings.tally) {
          delete ctrl.display.settings.tally;
          ctrl.display.settings.columns.forEach((col) => delete col.tally);
        } else {
          ctrl.display.settings.tally = {label: ts('Total')};
          ctrl.display.settings.columns.forEach(function(col) {
            if (col.type === 'field') {
              col.tally = {
                fn: searchMeta.getDefaultAggregateFn(searchMeta.parseExpr(ctrl.parent.getExprFromSelect(col.key)), ctrl.apiParams)
              };
            }
          });
        }
      };

      this.getTallyFunctions = function() {
        const allowedFunctions = _.filter(CRM.crmSearchAdmin.functions, function(fn) {
          return fn.category === 'aggregate' && fn.params.length;
        });
        return {results: formatForSelect2(allowedFunctions, 'name', 'title', ['description'])};
      };

      this.toggleTallyRewrite = function(col) {
        if (col.tally.rewrite) {
          delete col.tally.rewrite;
        } else {
          col.tally.rewrite = '[' + col.key + ']';
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
