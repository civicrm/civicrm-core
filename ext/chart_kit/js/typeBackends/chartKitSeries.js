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
        scaleTypes: ['date', 'numeric', 'categorical', 'integer'],
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

      // The datapoint key is a composite array that buildDimension builds from
      // getDimensionColumns(), in that column order. Resolve the x and grouping (w)
      // positions by axis rather than assuming a fixed order: the dimension columns
      // are not guaranteed to come out as [w, x], and if x and w are transposed the
      // grouping value gets plotted on the x-axis (off the scale domain) and every
      // point collapses to x = 0, so no lines are drawn.
      const dimCols = displayCtrl.getDimensionColumns();
      const xIndex = dimCols.findIndex((col) => col.axis === 'x');
      const wIndex = dimCols.findIndex((col) => col.axis === 'w');

      displayCtrl.chart
        .dimension(displayCtrl.dimension)
        .group(displayCtrl.group)
        .valueAccessor((d) => displayCtrl.getFirstColumnForAxis('y').valueAccessor(d))
        .keyAccessor((d) => d.key[xIndex])
        .seriesAccessor((d) => d.key[wIndex]);

      displayCtrl.buildCoordinateGrid();
    }
  };
})(CRM.chart_kit.dc);

