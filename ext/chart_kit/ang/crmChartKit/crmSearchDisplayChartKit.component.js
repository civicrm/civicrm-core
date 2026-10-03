(function (angular, $) {
  "use strict";

  angular.module('crmChartKit').component('crmSearchDisplayChartKit', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      apiParams: '<',
      settings: '<',
      filters: '<',
      totalCount: '=?'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmChartKit/chartKitCanvas.html',
    controller: function($scope, $element) {
      // The display reports its count through onTotalCount; pass it on to a bound total-count
      this.$postLink = () => {
        if (this.hasOwnProperty('totalCount')) {
          $element.children()[0].onTotalCount.push((count) => $scope.$evalAsync(() => this.totalCount = count));
        }
      };
    }
  });
})(angular, CRM.$);
