(function (d3, dc, ts) {

  CRM.chart_kit = CRM.chart_kit || {};

  // this is the canonical definition of utils

  CRM.chart_kit.settings = {};

  CRM.chart_kit.settings.chartType = [
    {
      key: 'pie',
      label: ts('Pie'),
      icon: 'fa-pie-chart',
      backend: 'pie',
    },
    {
      key: 'row',
      label: ts('Row'),
      icon: 'fa-chart-bar',
      backend: 'row',
    },
    {
      key: 'line',
      label: ts('Line'),
      icon: 'fa-line-chart',
      backend: 'stack',
    },
    {
      key: 'bar',
      label: ts('Bar'),
      icon: 'fa-chart-column',
      backend: 'stack',
    },
    {
      key: 'area',
      label: ts('Area'),
      icon: 'fa-chart-area',
      backend: 'stack',
    },
    {
      key: 'series',
      label: ts('Series'),
      icon: 'fa-chart-gantt',
      backend: 'series',
    },
    {
      key: 'composite',
      label: ts('Combined'),
      icon: 'fa-layer-group',
      backend: 'composite',
    },
    {
      key: 'heatmap',
      label: ts('Heat Map'),
      icon: 'fa-table-cells-large',
      backend: 'heatmap',
    },
  ];

  // Provides pluggable reducer for use in the chart_kit crossfilter
  // - see `buildGroup` function in `crmSearchDisplayChartKit`
  // - in the UI these are exposed as "Stat type"
  CRM.chart_kit.settings.reduceType = [
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
  ];

  const datePrecisionParser = (v, options) => {
    const date = Date.parse(v);
    if (options.precision) {
      switch (options.precision) {
        case 'year':
          return d3.timeYear.floor(date).valueOf();
        case 'month':
          return d3.timeMonth.floor(date).valueOf();
        case 'week':
          return d3.timeWeek.floor(date).valueOf();
        case 'day':
          return d3.timeDay.floor(date).valueOf();
        case 'hour':
          return d3.timeHour.floor(date).valueOf();
      }
    }
    return date;
  };

  const dateFormatter = (v, options) => {
    const date = new Date(v);
    switch (options.precision) {
      case 'year':
        return date.toLocaleString(undefined, { year: 'numeric' });
      case 'month':
        return date.toLocaleString(undefined, { year: 'numeric', month: 'long' });
      case 'week':
        return date.toLocaleString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
      case 'day':
        return date.toLocaleString(undefined, { year: 'numeric', month: 'long', day: 'numeric' });
      case 'hour':
        return date.toLocaleString();
    }
    return date.toLocaleString();
  };

  /**
   * Canoncial options for configuring a chart kit column
   *
   * Some options may be constrained in specific contexts - e.g. a
   * particular axis may only allow some scaletypes
   */
  CRM.chart_kit.settings.scaleType = [
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
    ];

  CRM.chart_kit.settings.datePrecision = [
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
    ];

  CRM.chart_kit.settings.seriesType = [
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
    ];

  CRM.chart_kit.settings.dataLabelType = [
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
    ];
  CRM.chart_kit.settings.dataLabelFormatter = [
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
      // NOTE: this is currently used to render appropriately precise dates when using
      // the datePrecision options. expects date values to be stored as timestamps
      // TODO: allow configuring other date formats
      {
        key: "formatDate",
        label: "Date format",
        apply: dateFormatter
      }
    ];

})(CRM.chart_kit.d3, CRM.chart_kit.dc, CRM.ts('chart_kit'));
