(function(angular, $, _) {
  "use strict";

  angular.module('af').component('afTabset', {
    templateUrl: '~/af/afTabset.html',
    transclude: true,
    controller: function($scope, $element) {
      this.tabs = [];

      this.$onInit = function() {
      };

      this.add = function(tab) {
        this.tabs.push(tab);
        this.selectTab(0);
      };

      this.selectTab = function(tabIndex) {
        this.selectedTab = tabIndex;
        const panelWrapper = $element.find('div[ng-transclude]');
        panelWrapper.find('af-tab').each(function(i, tab) {
          $(tab).toggle(i === tabIndex);
        });
      };
    }

  });

  angular.module('af').component('afTab', {
    require: {
      tabset: '^afTabset',
    },
    bindings: {
      title: '@',
      icon: '@',
      count: '@',
    },
    controller: function($scope, $element) {
      this.$onInit = function() {
        $element.attr('role', 'tabpanel');
        this.tabset.add(this);
      };
    }

  });
})(angular, CRM.$, CRM._);
