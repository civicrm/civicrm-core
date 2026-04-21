(function (angular, $, _) {
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
    controller: () => {}
  });
})(angular, CRM.$, CRM._);
