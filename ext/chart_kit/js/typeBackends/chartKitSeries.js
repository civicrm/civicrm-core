(function (dc) {
  CRM.chart_kit = CRM.chart_kit || {};

  CRM.chart_kit.typeBackends = CRM.chart_kit.typeBackends || {};

  CRM.chart_kit.typeBackends.series = {
    adminTemplate: '~/crmChartKitAdmin/typeBackends/chartKitSeriesAdmin.html',

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

    showLegend: (displayCtrl) => (displayCtrl._settings.showLegend && displayCtrl._settings.showLegend !== 'none'),

    // the legend gets the series "name", which is the delisted value of the series column
    legendTextAccessor: (displayCtrl) => ((d) => displayCtrl.getFirstColumnForAxis('w').renderValue(d.name)),

    // fallback to a line chart if we dont have a grouping column yet
    getChartConstructor: (displayCtrl) => displayCtrl.getColumnsForAxis('w') ? dc.seriesChart : dc.lineChart,

    loadChartData: (displayCtrl) => {
      displayCtrl.chart.chart((displayCtrl._settings.seriesDisplayType === 'bar') ? dc.barChart : dc.lineChart);
      displayCtrl.chart
        .dimension(displayCtrl.dimension)
        .group(displayCtrl.group)
        .valueAccessor((d) => displayCtrl.getFirstColumnForAxis('y').valueAccessor(d))
        // note: the datapoint keys are an array [w_value, x_value]
        // see buildDimension on crmSearchDisplayChartKit
        .keyAccessor((d) => d.key[1])
        .seriesAccessor((d) => d.key[0]);

      displayCtrl.buildCoordinateGrid();
    }
  };
})(CRM.chart_kit.dc);

