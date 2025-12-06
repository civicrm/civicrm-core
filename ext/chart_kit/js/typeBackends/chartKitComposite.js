(function (dc) {
  CRM.chart_kit = CRM.chart_kit || {};

  CRM.chart_kit.typeBackends = CRM.chart_kit.typeBackends || {};

  CRM.chart_kit.typeBackends.composite = {
    adminTemplate: '~/crmChartKitAdmin/typeBackends/chartKitCompositeAdmin.html',

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

    showLegend: (displayCtrl) => (displayCtrl._settings.showLegend && displayCtrl._settings.showLegend !== 'none'),

    getChartConstructor: () => dc.compositeChart,

    loadChartData: (displayCtrl) => {
      displayCtrl.chart
        .dimension(displayCtrl.dimension);

      // get our y columns
      const yAxisColumns = displayCtrl.getColumnsForAxis('y');

      // build color scale integrating user-assigned colors
      const colorScale = displayCtrl.buildColumnColorScale(yAxisColumns);

      // compose subchart for each column
      displayCtrl.chart
        // we need to add to main chart for axis building
        .dimension(displayCtrl.dimension)
        .group(displayCtrl.group)
        .shareTitle(false)
        .compose(yAxisColumns.map((col) => {
          // this is used to suppress other y cols from the labels
          const otherYColNames = yAxisColumns.map((otherCol) => otherCol.name).filter((name) => name !== col.name);

          const subChart = ((col.seriesType === 'bar') ? dc.barChart : dc.lineChart)(displayCtrl.chart);

          subChart
            .dimension(displayCtrl.dimension)
            // add group for this Y column
            .group(displayCtrl.group, col.label, (d) => col.valueAccessor(d))
            // set constant color using the color scale we made earlier
            .colorCalculator(() => colorScale(col.label))
            // title/label options on the subcharts
            // weblank out values from datapoint for other columns so the
            // rendered label matches this subchart
            .title((d) => displayCtrl.renderDataLabel(d, 'title', otherYColNames))
            .label((d) => displayCtrl.renderDataLabel(d, 'label', otherYColNames))
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
          displayCtrl._settings.barGap = Math.floor(Math.min(displayCtrl._settings.barGap, maxBarSpace - 1));
          displayCtrl._settings.barWidth = Math.floor(Math.min(displayCtrl._settings.barWidth, maxBarSpace - displayCtrl._settings.barGap));
          const barSpace = displayCtrl._settings.barWidth + displayCtrl._settings.barGap;
          const centerOffset = Math.floor((groupSpace - (barCount * barSpace)) / 2);

          yAxisColumns.forEach((col, subIndex) => {
            const offsetIndex = yAxisBars.findIndex((barCol) => barCol.name === col.name);
            if (offsetIndex < 0) {
              // not a bar
              return;
            }

            dc.transition(displayCtrl.chart.selectAll(`.sub._${subIndex} .bar`))
              .attr('width', displayCtrl._settings.barWidth)
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
  };
})(CRM.chart_kit.dc);

