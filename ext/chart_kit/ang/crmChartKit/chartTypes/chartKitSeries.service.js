(function (angular, $, _, dc) {
  "use strict";

  // common renderer for line/bar/area charts, which will stack by default
  // (compare with composite chart, where each column can be line/bar/area )
  angular.module('crmChartKit').factory('chartKitSeries', () => ({
    adminTemplate: '~/crmChartKitAdmin/chartTypes/chartKitSeriesAdmin.html',

    getInitialDisplaySettings: () => ({
      showLegend: 'right',
      seriesDisplayType: 'line',
    }),

    getAxes: () => ({
      'x': {
        label: ts('X-Axis'),
        scaleTypes: ['date', 'numeric', 'categorical'],
        reduceTypes: [],
        isDimension: true,
      },
      'w': {
        label: ts('Grouping'),
        scaleTypes: ['categorical'],
        reduceTypes: [],
        isDimension: true,
      },
      'y': {
        label: ts('Value'),
        sourceDataTypes: ['Integer', 'Money', 'Boolean'],
      },
      'z': {
        label: ts('Additional labels'),
        dataLabelTypes: ['title', 'label'],
        multiColumn: true,
        prepopulate: false,
      }
    }),

    hasCoordinateGrid: () => true,

    showLegend: (displayCtrl) => (displayCtrl.settings.showLegend && displayCtrl.settings.showLegend !== 'none'),

    // the legend gets the series "name", which is the delisted value of the series column
    legendTextAccessor: (displayCtrl) => ((d) => displayCtrl.renderDataValue(d.name, displayCtrl.getFirstColumnForAxis('w'))),

    // fallback to a line chart if we dont have a grouping column yet
    getChartConstructor: (displayCtrl) => displayCtrl.getColumnsForAxis('w') ? dc.seriesChart : dc.lineChart,

    loadChartData: (displayCtrl) => {
      displayCtrl.chart.chart((displayCtrl.settings.seriesDisplayType === 'bar') ? dc.barChart : dc.lineChart);
      displayCtrl.chart
        .dimension(displayCtrl.dimension)
        .group(displayCtrl.group)
        .valueAccessor(displayCtrl.getValueAccessor(displayCtrl.getFirstColumnForAxis('y')))
        .keyAccessor((d) => d.key[0])
        .seriesAccessor((d) => d.key[1]);

      displayCtrl.buildCoordinateGrid();
    }
  }));
})(angular, CRM.$, CRM._, CRM.chart_kit.dc);

