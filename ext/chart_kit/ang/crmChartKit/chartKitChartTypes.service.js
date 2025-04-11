(function (angular, $, _) {
  "use strict";

  // Provides pluggable chart types for use in the chart_kit display and admin components
  angular.module('crmChartKit').factory('chartKitChartTypes', (
    chartKitPie,
    chartKitRow,
    chartKitStack,
    chartKitComposite,
    chartKitSeries,
    chartKitHeatMap
  ) => {

    const ts = CRM.ts('chart_kit');

    const legacySettingsAdaptor = (settings) => {
      let updated = false;
      // for pie/row charts, x axis was moved to w. if pie/row chart has x
      // columns but no y columns, then transfer them
      if (settings.chartType === 'pie' || settings.chartType === 'row') {
        // if no cols for w axis
        if (!settings.columns.find((col) => col.axis === 'w')) {

          // update any x cols to w
          settings.columns.forEach((col, i) => {
            if (col.axis === 'x') {
              settings.columns[i].axis = 'w';
              updated = true;
            }
          });
        }
      }


      if (updated) {
        CRM.alert(ts("Please resave charts in SearchKit to avoid this message"), ts("Deprecated chart settings detected"));
      }
      return settings;
    };


    const types = [
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
      {
        key: 'heatmap',
        label: ts('Heat Map'),
        icon: 'fa-table-cells-large',
        service: chartKitHeatMap
      },
    ];

    return {
      types: types,
      legacySettingsAdaptor: legacySettingsAdaptor,
    };
  });
})(angular, CRM.$, CRM._);
