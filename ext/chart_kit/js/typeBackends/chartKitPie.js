(function (dc) {
  CRM.chart_kit = CRM.chart_kit || {};

  CRM.chart_kit.typeBackends = CRM.chart_kit.typeBackends || {};

  CRM.chart_kit.typeBackends.pie = {
    adminTemplate: '~/crmChartKitAdmin/typeBackends/chartKitPieAdmin.html',

    getAxes: () => ({
      'w': {
        label: ts('Category'),
        reduceTypes: ['list'],
        scaleTypes: ['categorical'],
        // label is default to show what things are
        dataLabelTypes: ['label', 'title', 'none'],
        multiColumn: true,
        isDimension: true,
      },
      'y': {
        label: ts('Values'),
        sourceDataTypes: ['Integer', 'Money', 'Boolean', 'Float', 'Double'],
      },
      'z': {
        label: ts('Additional labels'),
        dataLabelTypes: ['label', 'title'],
        prepopulate: false,
        multiColumn: true,
      }
    }),

    hasCoordinateGrid: () => false,

    showLegend: (displayCtrl) => (displayCtrl._settings.showLegend && displayCtrl._settings.showLegend !== 'none'),

    // the legend is cross product of column values from w columns
    legendTextAccessor: (displayCtrl) => ((d) =>
      (d.name === 'Others') ?
      ts('Others') :
      displayCtrl.getColumnsForAxis('w').map((col) => col.renderedValueAccessor(d)).join(' - ')),

    getInitialDisplaySettings: () => ({
      showLegend: 'left',
      maxSegments: 6,
    }),

    getChartConstructor: () => dc.pieChart,
  };
})(CRM.chart_kit.dc);

