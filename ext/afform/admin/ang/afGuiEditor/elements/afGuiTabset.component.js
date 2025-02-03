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

        this.node['#children'].forEach(function(tab, i) {
          tab['#children'] = tab['#children'] || [];
        });

        // Bootstrap3 doesn't handle the dropdown markup we're using (nesting the dropdown button inside the tabs)
        // So this emulates the bs3 dropdown.js functionality in AngularJS.
        // TODO: This is actually more efficient than bs3 because the menu can be removed from the dom instead of hidden,
        // so this probably ought to be turned into a directive and moved to crmUi.js
        $(document).on('click', function (e) {
          $scope.$evalAsync(() => handleClick(e));
        });

        // Show or hide a tab dropdown
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
          'title': ts('New Tab'),
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
          tab.icon = val;
        });
      };

      this.getDataEntity = function() {
        return $element.attr('data-entity') || '';
      };

    }
  });

})(angular, CRM.$, CRM._);
