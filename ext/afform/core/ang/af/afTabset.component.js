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
      pageNavButtons: '<',
      pageNavSubmitText: '<',
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
            const selectedName = CRM.cache.get(this.getCacheKey());
            this.selectedTab = this.tabs.findIndex((tab) => tab.name === selectedName);
          }
          if (this.selectedTab < 0 && this.tabs.length) {
            this.selectedTab = 0;
          }
        });

        if (this.rememberSelection) {
          // Watch for tab changes and remember the selection name
          $scope.$watch('$ctrl.getSelectedName', (newTab) => {
            if (newTab) {
              CRM.cache.set(this.getCacheKey(), newTab);
            }
          });
        }
      };

      this.addTab = (tab) => {
        this.tabs.push(tab);
      };

      this.selectTab = (tabIndex) => {
        // validate before moving forward
        if (tabIndex > this.selectedTab) {
          const currentInvalid = this.tabs[this.selectedTab]?.findInvalid();
          if (currentInvalid.length) {
            return;
          }
        }
        this.selectedTab = tabIndex;
      };

      this.getSelectedName = () => this.tabs[this.selectedTab]?.name;

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
      template: '<div ng-transclude role="tabpanel" ng-show="afTabsetCtrl.getSelectedName() === name" class="ng-hide"></div>',
      link: function (scope, element, attrs, afTabsetCtrl) {
        scope.name = scope.name || 'tab' + tabNumber++;
        scope.afTabsetCtrl = afTabsetCtrl;
        scope.findInvalid = () => element.find('.ng-invalid');
        afTabsetCtrl.addTab(scope);
      }
    };
  });
})(angular, CRM.$, CRM._);
