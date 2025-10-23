(function (dc) {
  CRM.chart_kit = CRM.chart_kit || {};

  CRM.chart_kit.typeBackends = CRM.chart_kit.typeBackends || {};

  CRM.chart_kit.typeBackends.row = {
    adminTemplate: '~/crmChartKitAdmin/typeBackends/chartKitRowAdmin.html',

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

    // TODO could add legend to row charts?
    showLegend: () => false,

    getInitialDisplaySettings: () => ({
      maxSegments: 10,
      chartOrderColIndex: 0,
      chartOrderDir: 'ASC',
    }),

    getChartConstructor: () => dc.rowChart,
  };
})(CRM.chart_kit.dc);

