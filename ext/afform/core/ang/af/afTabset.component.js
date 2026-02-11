(function(angular, $, _) {
  "use strict";

  angular.module('af').component('afTabset', {
    templateUrl: '~/af/afTabset.html',
    transclude: true,
    require: {
      'afForm': '?^^'
    },
    bindings: {
      'showNavButtons': '<',
    },
    controller: function($scope, $element) {
      this.tabs = [];

      this.$onInit = function() {
        $element.addClass('crm-tabset');
        this.tabSelected = 0;
      };

      this.addTab = function(tab) {
        tab.isSelected = !this.tabs.length;
        this.tabs.push(tab);
      };

      this.selectTab = function(tabIndex) {
        // validate before moving forward
        if (tabIndex > this.tabSelected) {
          const currentInvalid = this.tabs[this.tabSelected].findInvalid();
          if (currentInvalid.length) {
            return;
          }
        }
        this.tabSelected = tabIndex;
        this.tabs.forEach((tab, index) => tab.isSelected = (index === this.tabSelected));
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
      template: `
        <div ng-transclude role="tabpanel" ng-show="isSelected" class="ng-hide"></div>
      `,
      link: function (scope, element, attrs, afTabsetCtrl) {
        afTabsetCtrl.addTab(scope);
        scope.findInvalid = () => element.find('.ng-invalid');
      }
    };
  });

})(angular, CRM.$, CRM._);
