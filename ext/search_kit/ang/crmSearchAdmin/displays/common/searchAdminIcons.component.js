(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminIcons', {
    bindings: {
      item: '<'
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/displays/common/searchAdminIcons.html',
    controller: function($scope, $element, $timeout, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.getField = searchMeta.getField;

      this.fields = function() {
        let allFields = ctrl.crmSearchAdmin.getAllFields(':name', ['Field', 'Custom', 'Extra', 'Pseudo']);
        let selectFields = ctrl.crmSearchAdmin.getSelectFields();
        // Use machine names not labels for option matching
        selectFields.forEach((field) => field.id = field.id.replace(':label', ':name'));
        return {
          results: selectFields.concat(allFields)
        };
      };

      this.$onInit = function() {
        $element.on('hidden.bs.dropdown', function() {
          $timeout(function() {
            ctrl.menuOpen = false;
          });
        });
        const allFields = ctrl.crmSearchAdmin.getAllFields(':icon');
        let entityLabel = searchMeta.getEntity(ctrl.crmSearchAdmin.savedSearch.api_entity).title;
        // Gather all fields with an icon
        function getIconFields(iconFields, group, i) {
          if (group.children) {
            // Use singular title for main entity
            entityLabel = i ? group.text : entityLabel;
            _.transform(group.children, function(iconFields, field) {
              if (field.id && _.endsWith(field.id, 'icon')) {
                field.text = entityLabel + ' - ' + field.text;
                iconFields.push(field);
              }
            }, iconFields);
          }
        }
        ctrl.iconFields = _.transform(allFields, getIconFields, []);
        ctrl.iconFieldMap = _.indexBy(ctrl.iconFields, 'id');
      };

      this.onSelectField = function(clause) {
        if (clause[0]) {
          clause[1] = '=';
          clause.length = 2;
        } else {
          clause.length = 0;
        }
      };

      this.addIcon = function(field) {
        ctrl.item.icons = ctrl.item.icons || [];
        if (field) {
          ctrl.item.icons.push({field: field, side: 'left'});
        }
        else {
          searchMeta.pickIcon().then(function(icon) {
            if (icon) {
              ctrl.item.icons.push({icon: icon, side: 'left', if: []});
            }
          });
        }
      };

      this.pickIcon = function(index) {
        const item = ctrl.item.icons[index];
        searchMeta.pickIcon().then(function(icon) {
          if (icon) {
            item.icon = icon;
            delete item.field;
            item.if = item.if || [];
          }
        });
      };

      this.setIconField = function(field, index) {
        const item = ctrl.item.icons[index];
        delete item.icon;
        delete item.if;
        item.field = field;
      };

    }
  });

})(angular, CRM.$, CRM._);
