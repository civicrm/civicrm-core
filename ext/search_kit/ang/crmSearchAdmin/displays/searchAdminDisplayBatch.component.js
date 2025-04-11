(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminDisplayBatch', {
    bindings: {
      display: '<',
      apiEntity: '<',
      apiParams: '<'
    },
    require: {
      parent: '^crmSearchAdminDisplay'
    },
    templateUrl: '~/crmSearchAdmin/displays/searchAdminDisplayBatch.html',
    controller: function($scope, searchMeta, crmUiHelp) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit');
      const fieldSpecs = {};
      const ctrl = this;
      $scope.hs = crmUiHelp({file: 'CRM/Search/Help/Display'});

      this.includes = _.includes;

      // Add or remove an item from an array
      this.toggle = function(collection, item) {
        if (_.includes(collection, item)) {
          _.pull(collection, item);
        } else {
          collection.push(item);
        }
      };

      this.$onInit = function () {
        const isNew = !ctrl.display.settings;
        if (isNew) {
          ctrl.display.settings = {
            classes: ['table', 'table-striped', 'table-bordered', 'crm-sticky-header'],
            limit: CRM.crmSearchAdmin.defaultPagerSize,
            pager: {hide_single: true}
          };
        }
        ctrl.parent.initColumns({label: true});
        if (isNew) {
          this.toggleTally();
        }
      };

      this.hasDefault = function(col) {
        return 'default' in col;
      };

      this.toggleDefault = function(col) {
        if ('default' in col) {
          delete col.default;
        } else {
          col.default = null;
        }
      };

      this.toggleTally = function() {
        if (ctrl.display.settings.tally) {
          delete ctrl.display.settings.tally;
          ctrl.display.settings.columns.forEach((col) => delete col.tally);
        } else {
          ctrl.display.settings.tally = {};
          ctrl.display.settings.columns.forEach(function(col) {
            if (col.key) {
              const arg = searchMeta.parseExpr(col.key).args[0];
              if (!arg || !arg.field || arg.field.fk_entity || arg.field.options) {
                return;
              }
              if (arg.field.data_type === 'Integer' || arg.field.data_type === 'Float' || arg.field.data_type === 'Money') {
                col.tally = {fn: 'SUM'};
              }
              if (arg.field.data_type === 'Boolean') {
                col.tally = {fn: 'COUNT'};
              }
            }
          });
        }
      };

      this.getField = function(key) {
        if (key in fieldSpecs) {
          return fieldSpecs[key];
        }
        return (fieldSpecs[key] = searchMeta.getField(key));
      };

    }
  });

})(angular, CRM.$, CRM._);
