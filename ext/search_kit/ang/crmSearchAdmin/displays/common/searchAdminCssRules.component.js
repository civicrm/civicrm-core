(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchAdmin').component('searchAdminCssRules', {
    bindings: {
      item: '<',
      default: '<',
      label: '@',
    },
    require: {
      crmSearchAdmin: '^crmSearchAdmin'
    },
    templateUrl: '~/crmSearchAdmin/displays/common/searchAdminCssRules.html',
    controller: function($scope, $element, searchMeta) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.getField = searchMeta.getField;

      this.styles = _.transform(_.cloneDeep(CRM.crmSearchAdmin.styles), function(styles, style) {
        if (style.key !== 'default' && style.key !== 'secondary') {
          styles['bg-' + style.key] = style.value;
        }
      }, {});
      this.styles.disabled = ts('Disabled');
      this.styles['font-bold'] = ts('Bold');
      this.styles['font-italic'] = ts('Italic');
      this.styles.strikethrough = ts('Strikethrough');

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
          $scope.$apply(function() {
            ctrl.menuOpen = false;
          });
        });
      };

      this.onSelectField = function(clause) {
        if (clause[1]) {
          clause[2] = '=';
          clause.length = 3;
        } else {
          clause.length = 1;
        }
      };

      this.addClause = function(style) {
        const clause = [style];
        if (ctrl.default && ctrl.getField(ctrl.default)) {
          clause.push(ctrl.default, '=');
        }
        this.item.cssRules = this.item.cssRules || [];
        this.item.cssRules.push(clause);
      };

      this.showMore = function() {
        return !this.item.cssRules || !this.item.cssRules.length || _.last(this.item.cssRules)[1];
      };

    }
  });

})(angular, CRM.$, CRM._);
