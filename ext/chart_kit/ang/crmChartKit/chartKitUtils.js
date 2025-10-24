(function (d3, dc, ts) {

  CRM.chart_kit = CRM.chart_kit || {};

  // this is the canonical definition of utils, overwrite any other one
  CRM.chart_kit.utils = {};

  CRM.chart_kit.utils.legacySettingsAdaptor = (settings) => {
    let updated = false;
    // for pie/row charts, x axis was moved to w. if pie/row chart has x
    // columns but no y columns, then transfer them
    if (settings.chartType === 'pie' || settings.chartType === 'row') {
      // if no cols for w axis
      if (!settings.columns.find((col) => col.axis === 'w')) {

        // update any x cols to w
        settings.columns.forEach((col, i) => {
          if (col.axis === 'x') {
            settings.columns[i].axis = 'w';
            updated = true;
          }
        });
      }
    }

    if (settings.chartOrderColIndex) {
      if (!settings.columns[settings.chartOrderColIndex].isOrder) {
        settings.columns[settings.chartOrderColIndex].isOrder = true;
        updated = true;
      }
    }

    if (updated) {
      CRM.alert(ts("Please resave charts in SearchKit to avoid this message"), ts("Deprecated chart settings detected"));
    }
    return settings;
  };

  CRM.chart_kit.options.chartTypes = [
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
  CRM.chart_kit.options.reduceTypes = [
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

  /**
   * chartKitColumn service provides the ChartKitColumn class
   *
   * A "chart column" is a search field from the SearchKit SavedSearch
   * plus settings like reduceType and scaleType which control how data
   * in that field is processed when it is included in the chart
   */
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
   * chartKitColumn service provides the ChartKitColumn class
   *
   * A "chart column" is a search field from the SearchKit SavedSearch
   * plus settings like reduceType and scaleType which control how data
   * in that field is processed when it is included in the chart
   */

  CRM.chart_kit.column = class ChartKitColumn {

    constructor (
      name,
      axis,
      sourceKey,
      props
    ) {
      this.name = name;
      this.axis = axis;
      this.sourceKey = sourceKey;

      // props from the props object
      this.label = props.label;
      this.isDimension = props.isDimension;
      this.isOrder = props.isOrder;
      this.scaleType = props.scaleType;
      this.reduceType = props.reduceType ? props.reduceType : 'list';
      this.seriesType = props.seriesType;
      this.dataLabelType = props.dataLabelType;
      this.dataLabelColumnPrefix = props.dataLabelColumnPrefix;
      this.useRightAxis = props.useRightAxis;
      this.color = props.color;

      this.total = null;

      this.reducer = CRM.chart_kit.options.reduceType.find((type) => type.key === (this.reduceType));

      this.parsers = [];
      this.formatters = [];

      if (this.scaleType === 'categorical') {
        // category columns create a category list and then
        // just use indexes from the list whilst processing data
        this.categories = [];

        this.parseCategory = (v) => {
          const existingIndex = this.categories.indexOf(v);

          if (existingIndex < 0) {
            // if not found, add new category to our list
            this.categories.push(v);
            // we know this category is the last item in the category list
            return this.categories.length - 1;
          }

          return existingIndex;
        };

        this.renderCategory = (i) => {
          return this.categories[i];
        };

        this.parsers.unshift([this.parseCategory, {}]);
        this.formatters.push([this.renderCategory, {}]);
      }

      // the date precision argument sets a parser and formatter
      if (props.datePrecision) {
        this.parsers.unshift([datePrecisionParser, {precision: props.datePrecision}]);
        this.formatters.push([dateFormatter, {precision: props.datePrecision}]);
      }
      // add rounding or money format formatters
      // NOTE: these are mutually exclusive with date formatter and each other
      else if (props.dataLabelFormatter) {
        const formatter = CR.dataLabelFormatter.find((formatter) => formatter.key === props.dataLabelFormatter);

        if (formatter && formatter.apply) {
          // TODO: better way to provide these?
          const options = {
            decimalPlaces: props.dataLabelDecimalPlaces,
            moneyFormatString: props.dataLabelMoneyFormatString,
          };
          this.formatters.push([formatter.apply, options]);
        }
      }
    }

    applyParsers(v) {
      this.parsers.forEach((parserWithOptions) => {
        const [parser, options] = parserWithOptions;
        v = parser(v, options);
      });

      return v;
    }

    applyFormatters(v) {
      this.formatters.forEach((formatterWithOptions) => {
        const [formatter, options] = formatterWithOptions;
        v = formatter(v, options);
      });
      return v;
    }

    valueAccessor(d) {
      if (d.data && !d.value) {
        d.value = d.data;
      }
      const stored = d.value[this.name];
      if (stored === undefined) {
        return null;
      }
      return this.reducer.final(stored, this.total);
    }

    renderValue(v) {
      if (!v && v !== 0) {
        return null;
      }
      if (this.reducer.render) {
        return this.reducer.render(v, (v) => this.applyFormatters(v));
      } else {
        return this.applyFormatters(v);
      }
    }

    renderedValueAccessor(d) {
      const v = this.valueAccessor(d);
      return this.renderValue(v);
    }

    getRenderedLabel(d) {
      const v = this.renderedValueAccessor(d);

      if (this.dataLabelColumnPrefix) {
        return `${this.label}: ${v}`;
      }

      return v;
    }

    setTotal(v) {
      this.total = v;
    }
  };

})(CRM.chart_kit.d3, CRM.chart_kit.dc, CRM.ts('chart_kit'));
