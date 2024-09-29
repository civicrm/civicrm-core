(function (angular, $, _, dc) {
    "use strict";

    // common renderer for line/bar/area charts, which will stack by default
    // (compare with composite chart, where each column can be line/bar/area )
    angular.module('crmChartKit').factory('chartKitSeries', () => ({
        adminTemplate: '~/crmChartKit/chartTypes/chartKitSeriesAdmin.html',

        getInitialDisplaySettings: () => ({
          showLegend: 'right',
          seriesDisplayType: 'line',
        }),

        getAxes: function () {
            return ({
            'x': {
                label: ts('X-Axis'),
                scaleTypes: ['date', 'numeric', 'categorical'],
                reduceTypes: [],
            },
            'w': {
                label: ts('Grouping'),
                scaleTypes: ['categorical'],
                reduceTypes: [],
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
          });
        },

        hasCoordinateGrid: () => true,

        showLegend: (displayCtrl) => (displayCtrl.settings.showLegend && displayCtrl.settings.showLegend !== 'none'),

        // the legend gets the series "name", which is the delisted value of the series column
        legendTextAccessor: (displayCtrl) => ((d) => displayCtrl.renderDataValue(d.name, displayCtrl.getColumnsForAxis('w')[0])),

        // fallback to a line chart if we dont have a grouping column yet
        getChartConstructor: (displayCtrl) => displayCtrl.getColumnsForAxis('w') ? dc.seriesChart : dc.lineChart,

        buildDimension: (displayCtrl) => {
            // we need to add the series values in the dimension or they will get
            // aggregated
            displayCtrl.dimension = displayCtrl.ndx.dimension((d) => {
                const xValue = d[displayCtrl.getXColumn().index];
                const seriesCol = displayCtrl.getColumnsForAxis('w').length ? displayCtrl.getColumnsForAxis('w')[0] : null;
                const seriesVal = seriesCol ? d[seriesCol.index] : null;

                // we use a string separator rather than array to
                // not corrupt ordering on xValue
                return [xValue, seriesVal];
            });
        },

        loadChartData: (displayCtrl) => {
            displayCtrl.chart.chart((displayCtrl.settings.seriesDisplayType === 'bar') ? dc.barChart : dc.lineChart);
            displayCtrl.chart
                .dimension(displayCtrl.dimension)
                .group(displayCtrl.group)
                .valueAccessor(displayCtrl.getValueAccessor(displayCtrl.getColumnsForAxis('y')[0]))
                .keyAccessor((d) => d.key[0])
                .seriesAccessor((d) => d.key[1]);

            displayCtrl.buildCoordinateGrid();
        }
    }));
})(angular, CRM.$, CRM._, CRM.chart_kit.dc);

