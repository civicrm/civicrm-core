(function(angular, $, _) {
  "use strict";

  // Provides pluggable chart types for use in the chart_kit display and admin components
  angular.module('crmChartKit').factory('chartKitTypes', (chartKitPie, chartKitRow, chartKitStack, chartKitComposite) => {
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
            icon: 'fa-bar-chart fa-rotate-90',
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
            icon: 'fa-bar-chart',
            service: chartKitStack
        },
        {
            key: 'area',
            label: ts('Area'),
            icon: 'fa-area-chart',
            service: chartKitStack
        },
        {
            key: 'composite',
            label: ts('Combined'),
            icon: 'fa-bar-chart',
            service: chartKitComposite
        },
    ];
  });
})(angular, CRM.$, CRM._);
