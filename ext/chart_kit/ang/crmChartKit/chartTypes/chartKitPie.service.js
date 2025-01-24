(function(angular, $, _, dc) {
    "use strict";

    angular.module('crmChartKit').factory('chartKitPie', () => ({
      adminTemplate: '~/crmChartKit/chartTypes/chartKitPieAdmin.html',

      getAxes: () => ({
        'x': {
          label: ts('Category'),
          reduceTypes: [],
          scaleTypes: ['categorical'],
          // label is default to show what things are
          dataLabelTypes: ['label', 'title', 'none'],
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

      showLegend: (displayCtrl) => (displayCtrl.settings.showLegend && displayCtrl.settings.showLegend !== 'none'),

      // for pie chart the legend is showing column values, which benefit from rendering
      legendTextAccessor: (displayCtrl) => ((d) => (d.name === 'Others') ? 'Others' : displayCtrl.renderColumnValue(d.data, displayCtrl.getXColumn())),

      getInitialDisplaySettings: () => ({
        showLegend: 'left',
        maxSegments: 6,
      }),

      getChartConstructor: () => dc.pieChart,
    }));
})(angular, CRM.$, CRM._, CRM.chart_kit.dc);

