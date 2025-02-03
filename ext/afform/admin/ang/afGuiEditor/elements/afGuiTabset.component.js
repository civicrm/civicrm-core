// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  angular.module('afGuiEditor').component('afGuiTabset', {
    templateUrl: '~/afGuiEditor/elements/afGuiTabset.html',
    bindings: {
      node: '<',
      entityName: '<',
      deleteThis: '&'
    },
    require: {
      editor: '^^afGuiEditor',
    },
    controller: function($scope, $element, afGui) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform_admin'),
        ctrl = this;

      this.$onInit = function() {
        this.selectedTab = 0;

        // Dropdown menus toggled from
        $(document).on('click', function (e) {
          $scope.$evalAsync(() => handleClick(e));
        });

        function handleClick(e) {
          if ($($element).has(e.target).length) {
            const thisTab = $(e.target).closest('button[data-tab-id]');
            if (thisTab.length) {
              const tabIndex = thisTab.data('tabId');
              ctrl.menuOpen = ctrl.menuOpen === tabIndex ? false : tabIndex;
              return;
            }
          }
          ctrl.menuOpen = false;
        }
      };

      this.addTab = function() {
        this.node['#children'].push({
          '#tag': 'af-tab',
          'crm-title': ts('New Tab'),
          '#children': [],
        });
        this.selectTab(this.node['#children'].length - 1);
      };

      this.deleteTab = function(tabIndex) {
        this.node['#children'].splice(tabIndex, 1);
        if (this.selectedTab >= this.node['#children'].length) {
          this.selectedTab = this.node['#children'].length - 1;
        }
      };

      this.selectTab = function(tabIndex) {
        this.selectedTab = tabIndex;
      };

      this.pickIcon = function(tab) {
        afGui.pickIcon().then(function(val) {
          tab['crm-icon'] = val;
        });
      };

      this.getDataEntity = function() {
        return $element.attr('data-entity') || '';
      };

    }
  });

})(angular, CRM.$, CRM._);
