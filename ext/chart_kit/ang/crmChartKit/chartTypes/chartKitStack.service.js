(function (angular, $, _, dc) {
  "use strict";

  // common renderer for line/bar/area charts, which will stack by default
  // (compare with composite chart, where each column can be line/bar/area )
  angular.module('crmChartKit').factory('chartKitStack', () => ({
    adminTemplate: '~/crmChartKitAdmin/chartTypes/chartKitStackAdmin.html',

    getInitialDisplaySettings: () => ({}),

    getAxes: () => ({
      'x': {
        label: ts('X-Axis'),
        scaleTypes: ['date', 'numeric', 'categorical'],
        reduceTypes: [],
        isDimension: true,
      },
      'y': {
        key: 'y',
        label: ts('Values'),
        sourceDataTypes: ['Integer', 'Money', 'Boolean', 'Float', 'Double'],
        multiColumn: true,
        colorType: 'one-per-column',
      },
      'z': {
        label: ts('Additional Labels'),
        dataLabelTypes: ['label', 'title'],
        prepopulate: false,
        multiColumn: true,
      }
    }),

    hasCoordinateGrid: () => true,

    showLegend: (displayCtrl) => (displayCtrl.getColumnsForAxis('y').length > 1 && displayCtrl.settings.showLegend && displayCtrl.settings.showLegend !== 'none'),

    getChartConstructor: (displayCtrl) => (displayCtrl.settings.chartType === 'bar') ? dc.barChart : dc.lineChart,

    loadChartData: (displayCtrl) => {
      displayCtrl.chart
        .dimension(displayCtrl.dimension);

      const yAxisColumns = displayCtrl.getColumnsForAxis('y');

      // get the first y column for the initial group
      const firstY = yAxisColumns[0];

      displayCtrl.chart.group(displayCtrl.group, firstY.label, displayCtrl.getValueAccessor(firstY));

      // if we have more left then stack others on top
      yAxisColumns.slice(1).forEach((col) =>
        displayCtrl.chart.stack(displayCtrl.group, col.label, displayCtrl.getValueAccessor(col))
      );

      displayCtrl.chart.colors(displayCtrl.buildColumnColorScale(yAxisColumns));

      if (displayCtrl.settings.chartType === 'area') {
        // chart should be a line chart by this point
        displayCtrl.chart.renderArea(true);
      }
    }
  }));
})(angular, CRM.$, CRM._, CRM.chart_kit.dc);

