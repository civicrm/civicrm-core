(function (dc) {
  CRM.chart_kit = CRM.chart_kit || {};

  CRM.chart_kit.typeBackends = CRM.chart_kit.typeBackends || {};

  CRM.chart_kit.typeBackends.stack = {
    adminTemplate: '~/crmChartKitAdmin/typeBackends/chartKitStackAdmin.html',

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

    showLegend: (displayCtrl) => (displayCtrl.getColumnsForAxis('y').length > 1 && displayCtrl._settings.showLegend && displayCtrl._settings.showLegend !== 'none'),

    getChartConstructor: (displayCtrl) => (displayCtrl._settings.chartType === 'bar') ? dc.barChart : dc.lineChart,

    loadChartData: (displayCtrl) => {
      displayCtrl.chart
        .dimension(displayCtrl.dimension);

      const yAxisColumns = displayCtrl.getColumnsForAxis('y');

      // get the first y column for the initial group
      const firstY = yAxisColumns[0];

      displayCtrl.chart.group(displayCtrl.group, firstY.label, (d) => firstY.valueAccessor(d));

      // if we have more left then stack others on top
      yAxisColumns.slice(1).forEach((col) =>
        displayCtrl.chart.stack(displayCtrl.group, col.label, (d) => col.valueAccessor(d))
      );

      displayCtrl.chart.colors(displayCtrl.buildColumnColorScale(yAxisColumns));

      if (displayCtrl._settings.chartType === 'area') {
        // chart should be a line chart by this point
        displayCtrl.chart.renderArea(true);
      }
    }
  };
})(CRM.chart_kit.dc);

