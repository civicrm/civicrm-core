(function(angular, $, _) {
  "use strict";

  angular.module('af').component('afTabset', {
    templateUrl: '~/af/afTabset.html',
    transclude: true,
    controller: function($scope, $element) {
      this.tabs = [];

      this.$onInit = function() {
        $element.addClass('crm-tabset');
      };

      this.addTab = function(tab) {
        tab.tabSelected = !this.tabs.length;
        this.tabs.push(tab);
      };

      this.selectTab = function(tabIndex) {
        this.tabs.forEach(function(tab, index) {
          tab.tabSelected = index === tabIndex;
        });
      };
    }

  });

  angular.module('af').directive('afTab', function() {
    return {
      restrict: 'E',
      require: '^afTabset',
      scope: {
        title: '@',
        icon: '@',
        count: '@',
      },
      // Transclude allows the tab scope to be accessed from the inner html as $parent
      transclude: true,
      // ngShow will toggle the class `ng-hide`; also adding it to the markup avoids initial flash
      template: '<div ng-transclude role="tabpanel" ng-show="tabSelected" class="ng-hide"></div>',
      link: function (scope, element, attrs, afTabsetCtrl) {
        afTabsetCtrl.addTab(scope);
      }
    };
  });
})(angular, CRM.$, CRM._);
