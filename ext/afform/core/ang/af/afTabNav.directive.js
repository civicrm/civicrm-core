// https://civicrm.org/licensing
(function(angular, $, _) {
  "use strict";

  // Attribute directive applied to next/back tab navigation buttons.
  // Uses require to reach the ancestor afTabset controller regardless of transclusion.
  angular.module('af').directive('afTabNav', function() {
    return {
      restrict: 'A',
      bindToController: {},
      require: {
        afTabset: '?^^afTabset',
      },
      controller: function($scope, $element) {
        this.$onInit = () => {
          if (!this.afTabset) {
            return;
          }
          const direction = $element.attr('af-tab-nav');

          $element.on('click', (e) => {
            e.preventDefault();
            $scope.$apply(() => {
              if (direction === 'next') {
                this.afTabset.nextTab();
              } else {
                this.afTabset.prevTab();
              }
            });
          });

          if (direction === 'back') {
            $scope.$watch(
              () => this.afTabset.isFirstTab(),
              (isFirst) => $element.prop('disabled', isFirst)
            );
          } else {
            $scope.$watch(
              () => this.afTabset.isLastTab(),
              (isLast) => $element.prop('disabled', isLast)
            );
          }
        };
      }
    };
  });

})(angular, CRM.$, CRM._);
