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
        this.searchDisplays = [];

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
        ctrl.editor.onRemoveElement();
        this.selectTab(0);
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

      // When opening the menu, fetch search displays to show in the `af-gui-tab-count` select
      this.getSearchDisplays = function(tabIndex) {
        const displayTags = afGui.getFormElements(this.node['#children'][tabIndex]['#children'], (item) => (item['#tag'] && item['#tag'].startsWith('crm-search-display-') && item['search-name']));
        this.searchDisplays[tabIndex] = displayTags.map(item => {
          return {
            tag: item,
            defn: afGui.getSearchDisplay(item['search-name'], item['display-name'])
          };
        });
      };

      // Set a search display in the tab to have the `total-count` attribue which will control the count shown in the tab
      function getSetCount(tabIndex, displayIndex) {
        if (arguments.length === 1) {
          return ctrl.searchDisplays[tabIndex].findIndex(item => item.tag['total-count'] === '$parent.count');
        }
        ctrl.searchDisplays[tabIndex].forEach((item, index) => {
          if (index === displayIndex) {
            item.tag['total-count'] = '$parent.count';
          } else {
            delete item.tag['total-count'];
          }
        });
      }

      this.getSetCount = function (tabIndex) {
        return _.wrap(tabIndex, getSetCount);
      };

    }
  });

})(angular, CRM.$, CRM._);
