(function (d3, dc, ts, chartKitColumnOptions) {

  CRM.chart_kit = CRM.chart_kit || {};

  /**
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

      this.reducer = chartKitColumnOptions.reduceType.find((type) => type.key === (this.reduceType));

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
        this.parsers.unshift([this.datePrecisionParser, {precision: props.datePrecision}]);
        this.formatters.push([this.dateFormatter, {precision: props.datePrecision}]);
      }
      // add rounding or money format formatters
      // NOTE: these are mutually exclusive with date formatter and each other
      else if (props.dataLabelFormatter) {
        const formatter = chartKitColumnOptions.dataLabelFormatter.find((formatter) => formatter.key === props.dataLabelFormatter);

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

    /* TODO: merge these into the date precision options */
    datePrecisionParser(v, options) {
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
    }

    dateFormatter(v, options) {
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
    }
  };

})(CRM.chart_kit.d3, CRM.chart_kit.dc, CRM.ts('chart_kit'), CRM.chart_kit.columnOptions);
