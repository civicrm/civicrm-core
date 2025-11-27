(function (d3, dc, ts) {

  CRM.chart_kit = CRM.chart_kit || {};

  /**
   * Option lists for configuring a chart kit column
   *
   * Some options may be constrained in specific contexts - e.g. a
   * particular axis may only allow some scaletypes
   */
  CRM.chart_kit.columnOptions = {
    // Provides pluggable reducer for use in the chart_kit crossfilter
    // - see `buildGroup` function in `civiSearchDisplayChartKit`
    // - in the UI these are exposed as "Stat type"
    reduceType: [
      {
        key: 'sum',
        label: ts('Sum'),
        final: (f) => f,
        add: (p, v) => p + v,
        sub: (p, v) => p - v,
        start: () => 0
      },
      {
        key: 'count',
        label: ts('Count'),
        final: (f) => f,
        add: (p, v) => p + 1,
        sub: (p, v) => p - 1,
        start: () => 0
      },
      {
        key: 'mean',
        label: ts('Average'),
        final: (f) => f[0] / f[1],
        add: (p, v) => [
          p[0] + v,
          p[1] + 1,
        ],
        sub: (p, v) => [
          p[0] - v,
          p[1] - 1,
        ],
        start: () => [0, 0]
      },
      {
        key: "percentage_sum",
        label: ts("Percentage of total"),
        final: (f, total) => f / total,
        add: (p, v) => p + v,
        sub: (p, v) => p - v,
        start: () => 0,
        // note: percentage reducers dont support data render currently
        // though it could be used instead of floor to allow configurable
        // decimal places for percentage?
        render: (v, dataRender) => {
          const percentage = Math.floor(100 * v);
          return `${percentage}%`;
          }
      },
      {
        key: "percentage_count",
        label: ts("Percentage of count"),
        final: (f, total) => f / total,
        add: (p, v) => p + 1,
        sub: (p, v) => p - 1,
        start: () => 0,
        // note: percentage reducers dont support data render currently (see above)
        render: (v, dataRender) => {
          const percentage = Math.floor(100 * v);
          return `${percentage}%`;
        }
      },
      {
        key: 'list',
        label: ts('List'),
        final: (f) => f,
        add: (p, v) => {
          if (p.indexOf(v) < 0) {
            p.push(v);
          }
          return p;
        },
        sub: (p, v) => p.filter((x) => x != v),
        start: () => [],
        // apply the dataRender to each coordinate, then join
        render: (v, dataRender) => {
          if (!v.map) {
            return dataRender(v);
          }
          return v.map((item) => dataRender(item)).join(', ');
        },
      }
    ],
    scaleType: [
      {
        key: 'numeric',
        label: ts('Numeric'),
      },
      {
        key: 'categorical',
        label: ts('Categorical'),
      },
      {
        key: 'date',
        label: ts('Datetime'),
      },
    ],
    datePrecision: [
      {
        key: 'year',
        label: ts('Year'),
      },
      {
        key: 'month',
        label: ts('Month'),
      },
      {
        key: 'week',
        label: ts('Week'),
      },
      {
        key: 'day',
        label: ts('Day'),
      },
      {
        key: 'hour',
        label: ts('Hour'),
      },
    ],
    seriesType: [
      {
        key: 'bar',
        label: ts('Bar')
      },
      {
        key: 'line',
        label: ts('Line')
      },
      {
        key: 'area',
        label: ts('Area')
      },
    ],
    dataLabelType: [
      {
        key: "none",
        label: "None",
      },
      {
        key: "title",
        label: "On hover",
      },
      {
        key: "label",
        label: "Always show",
      }
    ],
    dataLabelFormatter: [
      {
        key: "none",
        label: "None",
      },
      {
        key: "round",
        label: "Round",
        apply: (v, options) => v.toFixed(options.decimalPlaces),
      },
      {
        key: "formatMoney",
        label: "Money formatter",
        apply: (v, options) => CRM.formatMoney(v, null, options.moneyFormatString),
      },
      // NOTE: dateFormatter is currently used to render appropriately precise dates when using
      // the datePrecision options. expects date values to be stored as timestamps
      // TODO: allow configuring other date formats
      // {
      //   key: "formatDate",
      //   label: "Date format",
      //   apply: utils.dateFormatter
      // }
    ],
  };

})(CRM.chart_kit.d3, CRM.chart_kit.dc, CRM.ts('chart_kit'));
