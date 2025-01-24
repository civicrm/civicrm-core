(function(angular, $, _, dc) {
    "use strict";

    angular.module('crmChartKit').factory('chartKitRow', () => ({
      adminTemplate: '~/crmChartKit/chartTypes/chartKitRowAdmin.html',

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

      // TODO could add legend to row charts?
      showLegend: () => false,

      getInitialDisplaySettings: () => ({
        maxSegments: 10,
        chartOrderColIndex: 0,
        chartOrderDir: 'ASC',
      }),

      getChartConstructor: () => dc.rowChart,
    }));
})(angular, CRM.$, CRM._, CRM.chart_kit.dc);

