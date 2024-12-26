(function(angular, $, _) {
  "use strict";

  angular.module('crmSearchDisplayTable').component('crmSearchDisplayToggleCollapse', {
    bindings: {
      rows: '<',
      rowIndex: '<',
    },
    templateUrl: '~/crmSearchDisplayTable/crmSearchDisplayToggleCollapse.html',
    controller: function($scope, $element) {
      const ts = $scope.ts = CRM.ts('org.civicrm.search_kit'),
        ctrl = this;

      this.$onInit = function() {
        const row = this.getRow();
        if (row.collapsed && row.data._descendents) {
          row.collapsed = false;
          this.toggleCollapsed();
        } else {
          row.collapsed = false;
        }
        // Button is irrelevant without any descendents; hide it without breaking the layout.
        if (!row.data._descendents) {
          $element.css('visibility', 'hidden');
        }
      };

      this.getRow = function () {
        return this.rows[this.rowIndex];
      };

      this.isCollapsed = function() {
        return this.getRow().collapsed && this.getRow().data._descendents;
      };

      this.countDescendents = function () {
        return this.getRow().data._descendents;
      };

      this.toggleCollapsed = function() {
        const row = this.getRow();
        row.collapsed = !row.collapsed;

        let descendentsEnd = this.rowIndex + row.data._descendents;
        for (let i = this.rowIndex + 1; i <= descendentsEnd; i++) {
          let hide = row.collapsed;
          // We're past the end of the page
          if (!ctrl.rows[i]) {
            return;
          }
          ctrl.rows[i].hidden = hide;
          // Hiding rows is simple, just hide all of them, but when un-hiding we need to skip over
          // the children of collapsed elements.
          if (!hide && ctrl.rows[i].collapsed) {
            i += ctrl.rows[i].data._descendents;
          }
        }
      };

    }
  });

})(angular, CRM.$, CRM._);
