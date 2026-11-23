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
      displayCtrl.chart
        .dimension(displayCtrl.dimension)
        .group(displayCtrl.group)
        .valueAccessor((d) => displayCtrl.getFirstColumnForAxis('y').valueAccessor(d))
        // note: the datapoint keys are an array [w_value, x_value]
        // see buildDimension on crmSearchDisplayChartKit
        .keyAccessor((d) => d.key[1])
        .seriesAccessor((d) => d.key[0]);

      displayCtrl.buildCoordinateGrid();

      // dc.seriesChart draws every series' bars at the same x, so with a bar display they
      // overlap and the taller series hides the shorter one. When showing bars, offset each
      // series within its category band so they sit side by side (a true grouped bar). Widths
      // and x are read from the band scale on each render (dc resets them from the scale before
      // this renderlet fires), so repeated renders do not accumulate the offset.
      if (displayCtrl._settings.seriesDisplayType === 'bar') {
        displayCtrl.chart.on('renderlet.groupSeriesBars', () => {
          const chart = displayCtrl.chart;
          const seriesCount = chart.selectAll('g.sub').nodes().length;
          if (seriesCount < 2) {
            return;
          }
          const bandWidth = (chart.x && chart.x().bandwidth) ? chart.x().bandwidth() : NaN;
          if (!Number.isFinite(bandWidth) || bandWidth <= 0) {
            return;
          }
          const gap = Math.min(4, Math.floor(bandWidth / (seriesCount * 4)));
          const barWidth = Math.max(1, Math.floor((bandWidth - (gap * (seriesCount - 1))) / seriesCount));
          for (let seriesIndex = 0; seriesIndex < seriesCount; seriesIndex++) {
            const offset = seriesIndex * (barWidth + gap);
            // Use chart.selectAll (dc's bundled d3 — there is no global d3 here) and offset via
            // transform, which is absolute, so repeated renders do not accumulate the shift.
            chart.selectAll(`.sub._${seriesIndex} rect.bar`)
              .attr('width', barWidth)
              .attr('transform', `translate(${offset}, 0)`);
          }
        });
      }
    }
  };
})(CRM.chart_kit.dc);

