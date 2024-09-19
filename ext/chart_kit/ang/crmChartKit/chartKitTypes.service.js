(function (angular, $, _) {
  "use strict";

  // Provides pluggable chart types for use in the chart_kit display and admin components
  angular.module('crmChartKit').factory('chartKitTypes', (
    chartKitPie,
    chartKitRow,
    chartKitStack,
    chartKitComposite,
    chartKitSeries
  ) => {

    const ts = CRM.ts('chart_kit');

    return [
      {
        key: 'pie',
        label: ts('Pie'),
        icon: 'fa-pie-chart',
        service: chartKitPie,
      },
      {
        key: 'row',
        label: ts('Row'),
        icon: 'fa-chart-bar',
        service: chartKitRow
      },
      {
        key: 'line',
        label: ts('Line'),
        icon: 'fa-line-chart',
        service: chartKitStack
      },
      {
        key: 'bar',
        label: ts('Bar'),
        icon: 'fa-chart-column',
        service: chartKitStack
      },
      {
        key: 'area',
        label: ts('Area'),
        icon: 'fa-chart-area',
        service: chartKitStack
      },
      {
        key: 'series',
        label: ts('Series'),
        icon: 'fa-chart-gantt',
        service: chartKitSeries
      },
      {
        key: 'composite',
        label: ts('Combined'),
        icon: 'fa-layer-group',
        service: chartKitComposite
      },
    ];
  });
})(angular, CRM.$, CRM._);
