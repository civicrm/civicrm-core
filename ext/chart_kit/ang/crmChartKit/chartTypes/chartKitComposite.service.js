(function (angular, $, _, dc) {
  "use strict";

  angular.module('crmChartKit').factory('chartKitComposite', () => ({
    adminTemplate: '~/crmChartKitAdmin/chartTypes/chartKitCompositeAdmin.html',

    getInitialDisplaySettings: () => ({
      barWidth: 10,
      barGap: 5,
    }),

    getAxes: () => ({
      'x': {
        label: ts('X-Axis'),
        // prefer date/categorical
        scaleTypes: ['date', 'numeric', 'categorical'],
        reduceTypes: [],
        isDimension: true,
      },
      'y': {
        key: 'y',
        label: ts('Values'),
        sourceDataTypes: ['Integer', 'Money', 'Boolean', 'Float', 'Double'],
        seriesTypes: ['bar', 'line', 'area'],
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

    showLegend: (displayCtrl) => (displayCtrl.settings.showLegend && displayCtrl.settings.showLegend !== 'none'),

    getChartConstructor: () => dc.compositeChart,

    loadChartData: (displayCtrl) => {
      displayCtrl.chart
        .dimension(displayCtrl.dimension);

      // get our y columns
      const yAxisColumns = displayCtrl.getColumnsForAxis('y');

      // build color scale integrating user-assigned colors
      const colorScale = displayCtrl.buildColumnColorScale(yAxisColumns);

      // in order to suppress other y-column labels on a given line
      // we apply a mask to set the datapoint value to null in that column
      const layerMask = (d, context, targetColIndex) => {
        d = displayCtrl.dataPointLabelMask(context)(d);

        return displayCtrl.settings.columns.map((col, colIndex) => {
          if ((col.axis === 'y') && (targetColIndex !== colIndex)) {
            return null;
          }
          return d[colIndex] ? d[colIndex] : null;
        });
      };

      // compose subchart for each column
      displayCtrl.chart
        // we need to add to main chart for axis building
        .dimension(displayCtrl.dimension)
        .group(displayCtrl.group)
        .shareTitle(false)
        .compose(yAxisColumns.map((col) => {

          const subChart = ((col.seriesType === 'bar') ? dc.barChart : dc.lineChart)(displayCtrl.chart);

          subChart
            .dimension(displayCtrl.dimension)
            // add group for this Y column
            .group(displayCtrl.group, col.label, displayCtrl.getValueAccessor(col))
            // set constant color using the color scale we made earlier
            .colorCalculator(() => colorScale(col.label))
            // title/label options on the subcharts
            // weblank out values from datapoint for other columns so the
            // rendered label matches this subchart
            .title((d) => displayCtrl.renderDataLabel(d, layerMask(d, 'title', col.index)))
            .label((d) => displayCtrl.renderDataLabel(d, layerMask(d, 'label', col.index)))
            .useRightYAxis(col.useRightAxis);

          if (col.seriesType === 'area') {
            subChart.renderArea(true);
          }

          return subChart;
        }));

      // dc doesn't deal with bars overlapping by default
      // so now we need to shrink them
      //
      // we do this based on barWidth & barGap settings
      // but we check to make sure these will fit
      const yAxisBars = yAxisColumns.filter((col) => col.seriesType === 'bar');
      const barCount = yAxisBars.length;

      if (barCount > 1) {
        displayCtrl.chart.on('renderlet.groupedBars', () => {
          const chart = displayCtrl.chart;
          const tickCount = chart.xUnitCount();
          const xAxisLength = chart.xAxisLength();

          // work out how much space we have total on the x-axis
          const groupSpace = Math.max(barCount, Math.floor(xAxisLength / (tickCount + 1)));
          const maxBarSpace = Math.max(1, Math.floor(groupSpace / barCount));

          // cap setting values below max
          displayCtrl.settings.barGap = Math.floor(Math.min(displayCtrl.settings.barGap, maxBarSpace - 1));
          displayCtrl.settings.barWidth = Math.floor(Math.min(displayCtrl.settings.barWidth, maxBarSpace - displayCtrl.settings.barGap));
          const barSpace = displayCtrl.settings.barWidth + displayCtrl.settings.barGap;
          const centerOffset = Math.floor((groupSpace - (barCount * barSpace)) / 2);

          yAxisColumns.forEach((col, subIndex) => {
            const offsetIndex = yAxisBars.findIndex((barCol) => barCol.index === col.index);
            if (offsetIndex < 0) {
              // not a bar
              return;
            }

            dc.transition(displayCtrl.chart.selectAll(`.sub._${subIndex} .bar`))
              .attr('width', displayCtrl.settings.barWidth)
              .attr('transform', `translate(${(offsetIndex * barSpace + centerOffset)}, 0)`);
            // move labels to align with bars
            dc.transition(displayCtrl.chart.selectAll(`.sub._${subIndex} .barLabel`))
              .attr('transform', `translate(${((offsetIndex - barCount + 0.5) * barSpace)}, 0)`);
          });
        });
      }
    },

    // helper for whether to display grouped bar settings in the admin screen
    isGroupedBar: (displayCtrl) => (displayCtrl.getColumnsForAxis('y').filter((col) => col.seriesType === 'bar').length > 1),
  }));
})(angular, CRM.$, CRM._, CRM.chart_kit.dc);

