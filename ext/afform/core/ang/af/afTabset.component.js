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
      orientation: '@',
      selectedTab: '=?',
      rememberSelection: '<',
      pageNavButtons: '<',
      pageNavSubmitText: '@',
    },
    controller: function($scope, $element, $location, $timeout) {
      const ts = $scope.ts = CRM.ts('org.civicrm.afform');

      this.tabs = [];

      this.$onInit = function() {
        $element.addClass('crm-tabset');
        this.isVertical = this.orientation === 'vertical';
        if (this.isVertical) {
          $element.addClass('crm-tabset-vertical');
        }

        if (this.urlArg) {
          // Afforms are not routed (@see afCore.js), so `$bindToRoute` is unavailable
          // outside the Angular SPA. Bind to the location's search params directly.
          this.selectedTab = $location.search()[this.urlArg] || this.selectedTab;
          $scope.$watch(() => $location.search()[this.urlArg], (tabName) => {
            if (tabName && tabName !== this.selectedTab) {
              this.selectTab(tabName);
            }
          });
          $scope.$watch('$ctrl.selectedTab', (tabName) => {
            if (tabName) {
              $location.search(this.urlArg, tabName);
            }
          });
        }

        $timeout(() => {
          // A url or cached selection may name a tab that no longer exists.
          if (this.selectedTab && this.findTabIndex(this.selectedTab) < 0) {
            this.selectedTab = null;
          }
          if (!this.selectedTab && this.rememberSelection) {
            const rememberedName = CRM.cache.get(this.getCacheKey());
            if (rememberedName) {
              this.selectTab(rememberedName);
            }
          }
          if (!this.selectedTab && this.tabs.length) {
            this.selectTab(this.tabs[0].name);
          }
        });

        if (this.rememberSelection) {
          // Watch for tab changes and remember the selection name
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
        const newIndex = this.findTabIndex(tabName);
        // Ignore a tab that doesn't exist, e.g. a stale url or remembered selection
        if (newIndex < 0) {
          return;
        }
        const currentIndex = this.findTabIndex(this.selectedTab);

        // validate before moving forward
        if (newIndex > currentIndex) {
          const currentInvalid = this.tabs[currentIndex]?.findInvalid();
          if (currentInvalid && currentInvalid.length) {
            return;
          }
        }
        this.selectedTab = tabName;
      };

      this.findTabIndex = (tabName) => this.tabs.findIndex((tab) => tab.name === tabName);

      this.hasPrevious = () => {
        const currentIndex = this.findTabIndex(this.selectedTab);
        return 0 < currentIndex;
      };

      this.hasNext = () => {
        const currentIndex = this.findTabIndex(this.selectedTab);
        return currentIndex < this.tabs.length - 1;
      };

      this.goToNext = () => {
        const currentIndex = this.findTabIndex(this.selectedTab);
        const nextTab = this.tabs[currentIndex + 1];
        if (nextTab) {
          this.selectTab(nextTab.name);
        }
      };

      this.goToPrevious = () => {
        const currentIndex = this.findTabIndex(this.selectedTab);
        const previousTab = this.tabs[currentIndex - 1];
        if (previousTab) {
          this.selectTab(previousTab.name);
        }
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
      template: '<div ng-transclude role="tabpanel" ng-show="afTabsetCtrl.selectedTab === name" class="ng-hide"></div>',
      link: function (scope, element, attrs, afTabsetCtrl) {
        scope.name = scope.name || 'tab' + tabNumber++;
        scope.afTabsetCtrl = afTabsetCtrl;
        scope.findInvalid = () => element.find('.ng-invalid');
        afTabsetCtrl.addTab(scope);
      }
    };
  });
})(angular, CRM.$, CRM._);
