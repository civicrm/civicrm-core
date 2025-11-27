(function (angular, $, _, dc, d3, crossfilter) {
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
})(angular, CRM.$, CRM._, CRM.chart_kit.dc, CRM.chart_kit.d3, CRM.chart_kit.crossfilter);
