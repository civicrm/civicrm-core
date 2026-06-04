(function(angular, $, _) {
  "use strict";

  let tabNumber = 0;

  angular.module('af').component('afTabset', {
    templateUrl: '~/af/afTabset.html',
    transclude: true,
    require: {
      afFormCtrl: '?^^afForm',
    },
    bindings: {
      urlArg: '@',
      selectedTab: '=?',
      rememberSelection: '<',
    },
    controller: function($scope, $element, $timeout) {
      this.tabs = [];

      this.$onInit = function() {
        $element.addClass('crm-tabset');

        if (this.urlArg) {
          $scope.$bindToRoute({
            expr: '$ctrl.selectedTab',
            param: this.urlArg,
            format: 'raw'
          });
        }

        $timeout(() => {
          if (!this.selectedTab && this.rememberSelection) {
            this.selectedTab = CRM.cache.get(this.getCacheKey());
          }
          if (!this.selectedTab && this.tabs.length) {
            this.selectedTab = this.tabs[0].name;
          }
        });

        if (this.rememberSelection) {
          // Watch for tab changes and remember the selection
          $scope.$watch('$ctrl.selectedTab', (newTab) => {
            if (newTab) {
              CRM.cache.set(this.getCacheKey(), newTab);
            }
          });
        }
      };

      this.addTab = (tab) => {
        this.tabs.push(tab);
      };

      this.selectTab = (tabName) => {
        this.selectedTab = tabName;
      };

      this.getFormName = () => this.afFormCtrl?.getFormMeta().name ?? $scope.$parent.meta.name;

      this.getCacheKey = () => this.getFormName() + 'SelectedTab';
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
        name: '@',
      },
      // Transclude allows the tab scope to be accessed from the inner html as $parent
      transclude: true,
      // ngShow will toggle the class `ng-hide`; also adding it to the markup avoids initial flash
      template: '<div ng-transclude role="tabpanel" ng-show="name === afTabsetCtrl.selectedTab" class="ng-hide"></div>',
      link: function (scope, element, attrs, afTabsetCtrl) {
        scope.name = scope.name || 'tab' + tabNumber++;
        scope.afTabsetCtrl = afTabsetCtrl;
        afTabsetCtrl.addTab(scope);
      }
    };
  });
})(angular, CRM.$, CRM._);
