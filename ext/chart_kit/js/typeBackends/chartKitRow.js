(function (d3, dc, ts) {

  CRM.chart_kit = CRM.chart_kit || {};
  CRM.chart_kit.typeBackends = CRM.chart_kit.typeBackends || {};

  /**
   * Horizontal bar chart, optionally stacked. dc.js's RowChart has no
   * stacking support (CapMixin, not StackMixin), so this is a standalone
   * chart class that renders one segment per 'y'-axis column per row via
   * d3.stack() - a single declared 'y' column (or a single Stack By
   * segment) degenerates to a plain, unstacked bar per row.
   */
  class RowChart extends dc.ColorMixin(dc.MarginMixin) {

    constructor(parent, chartGroup) {
      super();

      this._gap = 5;
      this._maxBarHeight = 22;
      this._legendPosition = 'top';
      this._x = undefined;
      this._xAxis = d3.axisBottom();
      this._yColumns = [];
      this._wColumns = [];
      this._zColumns = [];
      // Shared between _wrapLabel() and _fitLeftMargin(), which both need
      // to agree on how tall a wrapped line actually renders.
      this._lineHeightEm = 1.1;

      this.anchor(parent, chartGroup);
    }

    /**
     * Caps how tall a row's bar can get - remaining vertical space becomes
     * extra gap between rows instead of taller bars.
     * @param {Number} [height]
     * @returns {Number|RowChart}
     */
    maxBarHeight(height) {
      if (!arguments.length) {
        return this._maxBarHeight;
      }
      this._maxBarHeight = height;
      return this;
    }

    /**
     * 'top' or 'bottom' - where to place the horizontal, centered legend.
     * @param {String} [position]
     * @returns {String|RowChart}
     */
    legendPosition(position) {
      if (!arguments.length) {
        return this._legendPosition;
      }
      this._legendPosition = position;
      return this;
    }

    /**
     * The chart-kit 'y'-axis column configs to stack, in stacking order.
     * @param {Array} [cols]
     * @returns {Array|RowChart}
     */
    yColumns(cols) {
      if (!arguments.length) {
        return this._yColumns;
      }
      this._yColumns = cols;
      return this;
    }

    /**
     * The chart-kit 'w'-axis (category) column configs - joined together
     * to form the row label, same as Pie's legendTextAccessor.
     * @param {Array} [cols]
     * @returns {Array|RowChart}
     */
    wColumns(cols) {
      if (!arguments.length) {
        return this._wColumns;
      }
      this._wColumns = cols;
      return this;
    }

    /**
     * The chart-kit 'z'-axis (additional label) column configs - joined
     * alongside the 'w' columns, per column Data Label setting.
     * @param {Array} [cols]
     * @returns {Array|RowChart}
     */
    zColumns(cols) {
      if (!arguments.length) {
        return this._zColumns;
      }
      this._zColumns = cols;
      return this;
    }

    /**
     * 'w'/'z' columns configured to appear in the always-visible row label
     * vs. the hover-only tooltip - mirrors the shared renderDataLabel()'s
     * 'label'/'title' contexts, since RowChart draws its own text rather
     * than going through dc.js's .label()/.title() accessors.
     */
    _labelColumns() {
      return [...this._wColumns, ...this._zColumns].filter((col) => col.dataLabelType === 'label');
    }

    _titleColumns() {
      return [...this._wColumns, ...this._zColumns].filter((col) => col.dataLabelType === 'title');
    }

    _getRowLabel(g) {
      return this._labelColumns().map((col) => col.getRenderedLabel(g)).filter((label) => !!label).join(' - ');
    }

    _getRowTitle(g) {
      return this._titleColumns().map((col) => col.getRenderedLabel(g)).filter((label) => !!label).join(' - ');
    }

    /**
     * Column values must go through valueAccessor()/getRenderedLabel(), not
     * read off the raw group row: `.name` is the internal crossfilter key
     * (not the SQL alias), and categorical values are stored as an index
     * into the column's category list, not the display string.
     */
    _buildStack() {
      const rawGroups = this.data();
      const keys = this._yColumns.map((col) => col.name);
      const flat = rawGroups.map((g) => {
        const row = {};
        this._yColumns.forEach((col) => {
          row[col.name] = col.valueAccessor(g) || 0;
        });
        return row;
      });
      return {rawGroups, series: d3.stack().keys(keys)(flat)};
    }

    /**
     * Grows the configured left margin (never shrinks it) to fit every row
     * label within whatever number of wrapped lines actually fits in
     * `rowSlot` (a row's full share of vertical space, bar plus gap) -
     * wider rather than taller, since a squashed row slot (e.g. many rows,
     * or a short chart) leaves too little vertical room for a label
     * wrapped across several lines without overlapping its neighbours.
     * Falls back to the widest single word (the narrowest a label could
     * ever be wrapped to) once even one line per row doesn't fit. Capped
     * at 40% of the chart's width so one long label can't swallow the
     * whole chart at the bars' expense.
     */
    _fitLeftMargin(rawGroups, rowSlot) {
      const measure = this._g.append('text').style('visibility', 'hidden');
      const measureWidth = (text) => {
        measure.text(text);
        return measure.node().getComputedTextLength();
      };
      const lineHeightPx = (parseFloat(window.getComputedStyle(measure.node()).fontSize) || 12) * this._lineHeightEm;
      const maxLines = Math.max(1, Math.floor(rowSlot / lineHeightPx));
      // How many lines `words` wraps onto at a given width - same greedy
      // algorithm as _wrapLabel(), just measuring rather than rendering.
      const countLines = (words, width) => {
        let lines = 1;
        let line = [];
        words.forEach((word) => {
          const candidate = line.concat(word).join(' ');
          if (line.length && measureWidth(candidate) > width) {
            lines++;
            line = [word];
          }
          else {
            line.push(word);
          }
        });
        return lines;
      };

      let desired = 0;
      rawGroups.forEach((g) => {
        const words = (this._getRowLabel(g) || '').split(/\s+/).filter(Boolean);
        if (!words.length) {
          return;
        }
        const widestWord = Math.max(...words.map(measureWidth));
        const fullWidth = measureWidth(words.join(' '));
        // Binary search the narrowest width that still keeps this label
        // within maxLines - lo always fits (the full label on one line),
        // hi always fits too (nothing narrower than one word is possible).
        let lo = widestWord;
        let hi = fullWidth;
        while (hi - lo > 1) {
          const mid = (lo + hi) / 2;
          if (countLines(words, mid) <= maxLines) {
            hi = mid;
          }
          else {
            lo = mid;
          }
        }
        desired = Math.max(desired, hi);
      });
      measure.remove();

      const cap = this.width() * 0.4;
      this.margins().left = Math.max(this.margins().left, Math.min(desired + 12, cap));
    }

    _calculateAxisScale(maxValue) {
      const maxInt = Math.max(1, Math.ceil(maxValue || 0));
      this._x = d3.scaleLinear()
        .domain([0, maxInt])
        .range([0, this.effectiveWidth()]);
      // Force whole-number ticks - d3's default algorithm can pick
      // fractional steps for a small domain, but case counts are integers.
      const step = Math.max(1, Math.ceil(maxInt / 10));
      this._xAxis
        .scale(this._x)
        .tickValues(d3.range(0, maxInt + 1, step))
        .tickFormat(d3.format('d'));
    }

    _drawAxis() {
      let axisG = this._g.select('g.axis');
      if (axisG.empty()) {
        axisG = this._g.append('g').attr('class', 'axis');
      }
      axisG.attr('transform', `translate(0, ${this.effectiveHeight()})`);
      // Not dc.transition(): an interrupted transition (e.g. a second
      // render before the first completes) leaves attributes unset entirely.
      axisG.call(this._xAxis);
      // dc.css hardcodes axis lines to black, near-invisible on a dark theme.
      axisG.selectAll('path.domain, .tick line')
        .attr('stroke', 'var(--crm-border-color)')
        .attr('shape-rendering', 'crispEdges');

      // Vertical rule at x=0 separating row labels from bars; rows are
      // manually positioned, so there's no real y-axis to draw otherwise.
      let yAxisLine = this._g.select('line.y-axis-line');
      if (yAxisLine.empty()) {
        yAxisLine = this._g.append('line').attr('class', 'y-axis-line');
      }
      yAxisLine
        .attr('x1', 0)
        .attr('x2', 0)
        .attr('y1', 0)
        .attr('y2', this.effectiveHeight())
        .attr('stroke', 'var(--crm-border-color)')
        .attr('shape-rendering', 'crispEdges');
    }

    /**
     * Wraps `text` onto as many <tspan> lines as needed to fit `maxWidth`,
     * vertically centered on `centerY`. Line width is measured via
     * getComputedTextLength() on the real element, not estimated.
     */
    _wrapLabel(textSelection, text, maxWidth, centerY) {
      const words = text.split(/\s+/).filter(Boolean).reverse();
      textSelection.text(null);
      const x = textSelection.attr('x');
      const lineHeightEm = this._lineHeightEm;
      let line = [];
      const lines = [];
      let tspan = textSelection.append('tspan').attr('x', x);
      while (words.length) {
        const word = words.pop();
        line.push(word);
        tspan.text(line.join(' '));
        if (line.length > 1 && tspan.node().getComputedTextLength() > maxWidth) {
          line.pop();
          tspan.text(line.join(' '));
          lines.push(tspan);
          line = [word];
          tspan = textSelection.append('tspan').attr('x', x).text(word);
        }
      }
      lines.push(tspan);

      // A single word wider than maxWidth alone (the wrap loop above can
      // only break between words) would otherwise overflow past the
      // margin and get clipped by the chart's own SVG boundary.
      lines.forEach((ln) => this._truncateToFit(ln, maxWidth));

      const startDy = -((lines.length - 1) / 2) * lineHeightEm;
      lines.forEach((ln, i) => {
        ln.attr('y', centerY).attr('dy', `${startDy + i * lineHeightEm + 0.35}em`);
      });
    }

    /**
     * Shortens `tspan`'s text with a trailing ellipsis, one character at a
     * time, until it fits within maxWidth.
     */
    _truncateToFit(tspan, maxWidth) {
      let text = tspan.text();
      while (text.length > 1 && tspan.node().getComputedTextLength() > maxWidth) {
        text = text.slice(0, -1);
        tspan.text(`${text}…`);
      }
    }

    _doRender() {
      this.resetSvg();
      this.svg().classed('row-stack-chart-svg', true);
      // Scoped stylesheet rule rather than .attr()/.style(), for two reasons:
      // - Legend item width is measured synchronously inside
      //   Legend.render() via getBBox(), so font-size must already be
      //   correct at that point - patching it in afterwards is too late.
      // - The shared 'pretransition' listener sets text fill via .attr(),
      //   and SVG presentation attributes don't resolve CSS var(), so
      //   format.labelColor (a var() reference) silently falls back to
      //   black. A stylesheet rule always wins over that attribute in the
      //   cascade, regardless of timing.
      this.svg().append('style').text(`
        .row-stack-chart-svg .dc-legend-item text { font-size: var(--crm-font-size); }
        .row-stack-chart-svg text { fill: var(--crm-text-color) !important; }
      `);
      this._g = this.svg()
        .append('g')
        .attr('transform', `translate(${this.margins().left},${this.margins().top})`);
      this._drawChart();
      return this;
    }

    _doRedraw() {
      this._drawChart();
      return this;
    }

    /**
     * A segment's rendered value, formatted per its column's configured
     * Data Label Formatter (money/rounding/etc, via ChartKitColumn's
     * renderValue()) - or, in Stack By mode, deferring to the optional
     * weight column's formatter if one was configured.
     */
    _formatSegmentValue(d) {
      return d.col.renderValue(d.x1 - d.x0);
    }

    _drawChart() {
      const {rawGroups, series} = this._buildStack();

      const n = rawGroups.length;
      // Cap bar height and give any leftover vertical space to the gaps
      // between rows instead, so bars stay slim regardless of chart height.
      // Independent of margins - safe to compute before fitting the left
      // one, and _fitLeftMargin needs it to know how many lines fit.
      const rawHeight = n ? (this.effectiveHeight() - (n + 1) * this._gap) / n : 0;
      const height = n ? Math.max(1, Math.min(rawHeight, this._maxBarHeight)) : 0;
      const gap = n ? (this.effectiveHeight() - height * n) / (n + 1) : this._gap;

      // The label's line budget is the full row-to-row spacing (bar height
      // plus its surrounding gap), not just the bar's own height - bars
      // are deliberately kept slim (maxBarHeight) with the rest of the
      // space going to gap, and a vertically-centered label can use all of
      // that before it risks reaching the next row's label.
      this._fitLeftMargin(rawGroups, height + gap);
      // _g's own position was translated using the margins as they stood
      // when _doRender() first created it - re-sync now that _fitLeftMargin
      // may have grown margins().left, or everything below stays shifted
      // as if it hadn't.
      this._g.attr('transform', `translate(${this.margins().left},${this.margins().top})`);

      const maxValue = d3.max(series, (s) => d3.max(s, (d) => d[1]));
      this._calculateAxisScale(maxValue);
      this._drawAxis();

      const colorScale = this.colors();
      const columns = this._yColumns;
      const self = this;

      let rows = this._g.selectAll('g.row-stack')
        .data(rawGroups, (d) => d.key);
      rows.exit().remove();
      rows = rows.enter()
        .append('g')
        .attr('class', 'row-stack')
        .merge(rows)
        .attr('transform', (d, i) => `translate(0, ${(i + 1) * gap + i * height})`);

      rows.each(function (rowDatum, rowIndex) {
        const segments = columns.map((col, colIndex) => ({
          col,
          x0: series[colIndex][rowIndex][0],
          x1: series[colIndex][rowIndex][1],
        }));

        let rects = d3.select(this).selectAll('rect.segment')
          .data(segments, (d) => d.col.name);
        rects.exit().remove();
        rects = rects.enter()
          .append('rect')
          .attr('class', 'segment')
          .merge(rects);

        rects
          .attr('y', 0)
          .attr('height', height)
          .attr('x', (d) => self._x(d.x0))
          .attr('width', (d) => Math.max(0, self._x(d.x1) - self._x(d.x0)))
          .attr('fill', (d) => colorScale(d.col.label));

        // "None" skips the tooltip entirely; "title"/"label" both get one
        // (an always-visible label is still useful detail on hover).
        let titles = rects.selectAll('title')
          .data((d) => (d.col.dataLabelType === 'none' ? [] : [d]));
        titles.exit().remove();
        titles.enter().append('title').merge(titles)
          .text((d) => `${d.col.label}: ${self._formatSegmentValue(d)}`);

        // Always-visible value label per segment, e.g. "25" - skipped for
        // zero-width segments, and for "none"/"title" (hover-only) Data
        // Label settings. No fill set - the shared 'pretransition'
        // listener overwrites every <text> fill anyway.
        const valueLabels = segments.filter((d) => d.x1 - d.x0 > 0 && d.col.dataLabelType !== 'none' && d.col.dataLabelType !== 'title');
        let segmentLabels = d3.select(this).selectAll('text.segment-value')
          .data(valueLabels, (d) => d.col.name);
        segmentLabels.exit().remove();
        segmentLabels = segmentLabels.enter()
          .append('text')
          .attr('class', 'segment-value')
          .attr('text-anchor', 'middle')
          .merge(segmentLabels);
        segmentLabels
          .attr('x', (d) => (self._x(d.x0) + self._x(d.x1)) / 2)
          .attr('y', height / 2)
          .attr('dy', '0.35em')
          .text((d) => self._formatSegmentValue(d));
      });

      // Appended after the segments so it paints on top (SVG paints in DOM
      // order); positioned in the left margin gutter via negative x.
      let labels = rows.selectAll('text.row-stack-label')
        .data((d) => [d]);
      labels.exit().remove();
      labels = labels.enter()
        .append('text')
        .attr('class', 'row-stack-label')
        .attr('text-anchor', 'end')
        .merge(labels);
      labels
        .attr('x', -6)
        .attr('y', height / 2);
      const maxLabelWidth = Math.max(20, this.margins().left - 12);
      labels.each(function (d) {
        self._wrapLabel(d3.select(this), self._getRowLabel(d), maxLabelWidth, height / 2);
      });

      let rowTitles = labels.selectAll('title')
        .data((d) => (self._getRowTitle(d) ? [d] : []));
      rowTitles.exit().remove();
      rowTitles.enter().append('title').merge(rowTitles)
        .text((d) => self._getRowTitle(d));

      this._positionLegend();
    }

    /**
     * dc.js's Legend has no built-in "centered" concept, so it's rendered
     * once here just to measure its width via getBBox(), then repositioned
     * to center it horizontally.
     */
    _positionLegend() {
      const legend = this.legend();
      if (!legend) {
        return;
      }
      legend
        .horizontal(true)
        .autoItemWidth(true)
        .legendWidth(this.width())
        .gap(10);

      const y = this._legendPosition === 'bottom' ? this.height() - this.margins().bottom + 25 : 4;
      legend.x(0).y(y).render();

      const bbox = legend._g.node().getBBox();
      legend.x(Math.max(0, (this.width() - bbox.width) / 2));
    }

    legendables() {
      const colorScale = this.colors();
      return this._yColumns.map((col) => ({
        chart: this,
        name: col.label,
        color: colorScale(col.label),
      }));
    }

  }

  CRM.chart_kit.typeBackends.row = {
    adminTemplate: '~/crmChartKitAdmin/typeBackends/chartKitRowAdmin.html',

    getInitialDisplaySettings: () => ({
      maxSegments: 10,
      chartOrderColIndex: 0,
      chartOrderDir: 'ASC',
    }),

    getAxes: () => ({
      'w': {
        label: ts('Category'),
        reduceTypes: ['list'],
        scaleTypes: ['categorical'],
        dataLabelTypes: ['label', 'title', 'none'],
        multiColumn: true,
        isDimension: true,
      },
      // Alternative to declaring one 'y' column per segment: pick a single
      // categorical field here and a segment is created per distinct value
      // found in the data, counting (or summing 'y', if given) automatically.
      's': {
        label: ts('Stack By'),
        scaleTypes: ['categorical'],
        reduceTypes: ['list'],
        // not multiColumn (only one Stack By field makes sense) but still
        // genuinely optional - buildGroup() below falls back to plain
        // declared-column stacking when no 's' column is configured, so
        // don't auto-populate one, and let it be removed once added.
        prepopulate: false,
      },
      'y': {
        key: 'y',
        label: ts('Values'),
        sourceDataTypes: ['Integer', 'Money', 'Boolean', 'Float', 'Double'],
        multiColumn: true,
        colorType: 'one-per-column',
        dataLabelTypes: ['label', 'title', 'none'],
      },
      'z': {
        label: ts('Additional labels'),
        dataLabelTypes: ['label', 'title'],
        prepopulate: false,
        multiColumn: true,
      },
    }),

    hasCoordinateGrid: () => false,

    showLegend: (displayCtrl) => {
      const segments = displayCtrl.getFirstColumnForAxis('s') ? 2 : displayCtrl.getColumnsForAxis('y').length;
      return segments > 1 && displayCtrl._settings.showLegend && displayCtrl._settings.showLegend !== 'none';
    },

    getChartConstructor: () => ((parent, chartGroup) => new RowChart(parent, chartGroup)),

    // Default buildGroup (civi-search-display-chart-kit.js) reduces each
    // declared 'y' column by name - it has no way to produce one segment
    // per distinct value of a field chosen at render time, so 'stack by'
    // mode needs its own reduce entirely.
    buildGroup: (displayCtrl) => {
      const sCol = displayCtrl.getFirstColumnForAxis('s');
      if (!sCol) {
        buildDeclaredColumnGroup(displayCtrl);
        return;
      }
      // RowChart reads the row label/tooltip via w/z column valueAccessor(d),
      // which expects d.value[col.name] - constant within a dimension
      // group, so no reducer semantics needed for these, just carry them
      // through.
      const passthroughCols = [...displayCtrl.getColumnsForAxis('w'), ...displayCtrl.getColumnsForAxis('z')];
      const sKey = sCol.name;
      const yCol = displayCtrl.getFirstColumnForAxis('y');
      const yKey = yCol ? yCol.name : null;
      // Weight by the 'y' column if given - always summed, regardless of its
      // configured Stat Type (e.g. a pre-aggregated COUNT(*) per row, or a
      // real amount field) - otherwise each row counts as 1, so a plain
      // "pick 2 fields, no aggregate function" search works with no 'y' at
      // all. sCol.renderValue() is required: a categorical column's stored
      // value is an index into its own per-column category list built up
      // as buildCrossfilter() runs (see chartKitColumn.js), not the label.
      const weightOf = (v) => (yKey ? (v[yKey] || 0) : 1);
      const reduceAdd = (p, v) => {
        const key = sCol.renderValue(v[sKey]);
        p[key] = (p[key] || 0) + weightOf(v);
        passthroughCols.forEach((col) => { p[col.name] = v[col.name]; });
        return p;
      };
      const reduceSub = (p, v) => {
        const key = sCol.renderValue(v[sKey]);
        p[key] = (p[key] || 0) - weightOf(v);
        return p;
      };
      displayCtrl.group = displayCtrl.dimension.group().reduce(reduceAdd, reduceSub, () => ({}));
    },

    loadChartData: (displayCtrl) => {
      displayCtrl.chart
        .dimension(displayCtrl.dimension)
        .group(displayCtrl.group);

      // format.padding is normally applied via formatCoordinateGrid(), but
      // that's gated on hasCoordinateGrid() (false here), so apply it
      // directly or it's silently ignored.
      if (displayCtrl._settings.format && displayCtrl._settings.format.padding) {
        displayCtrl.chart.margins(displayCtrl._settings.format.padding);
      }

      const sCol = displayCtrl.getFirstColumnForAxis('s');
      const yAxisColumns = sCol ? buildStackByColumns(displayCtrl, sCol) : displayCtrl.getColumnsForAxis('y');
      displayCtrl.chart.yColumns(yAxisColumns);
      displayCtrl.chart.wColumns(displayCtrl.getColumnsForAxis('w'));
      displayCtrl.chart.zColumns(displayCtrl.getColumnsForAxis('z'));
      displayCtrl.chart.colors(displayCtrl.buildColumnColorScale(yAxisColumns));
      if (['top', 'bottom'].includes(displayCtrl._settings.showLegend)) {
        displayCtrl.chart.legendPosition(displayCtrl._settings.showLegend);
      }
      if (displayCtrl._settings.maxBarHeight) {
        displayCtrl.chart.maxBarHeight(displayCtrl._settings.maxBarHeight);
      }
    },
  };

  // Same reduce civi-search-display-chart-kit.js's default buildGroup uses,
  // duplicated here since defining our own buildGroup fully replaces it
  // (there's no way to fall through to the shared default from a backend).
  function buildDeclaredColumnGroup(displayCtrl) {
    const reduceAdd = (p, v) => {
      displayCtrl.getColumns().forEach((col) => { p[col.name] = col.reducer.add(p[col.name], v[col.name]); });
      return p;
    };
    const reduceSub = (p, v) => {
      displayCtrl.getColumns().forEach((col) => { p[col.name] = col.reducer.sub(p[col.name], v[col.name]); });
      return p;
    };
    const reduceStart = () => {
      const p = {};
      displayCtrl.getColumns().forEach((col) => { p[col.name] = col.reducer.start(); });
      return p;
    };
    displayCtrl.group = displayCtrl.dimension.group().reduce(reduceAdd, reduceSub, reduceStart);
    const columnTotals = displayCtrl.ndx.groupAll().reduce(reduceAdd, reduceSub, reduceStart).value();
    displayCtrl.getColumns().forEach((col) => col.setTotal(columnTotals[col.name]));
  }

  // One synthetic, duck-typed "column" per distinct value of the stack-by
  // field seen in the loaded data - RowChart only ever calls .name, .label,
  // .valueAccessor(g) and .renderValue(v) on a yColumn, so these don't need
  // to be real ChartKitColumn instances.
  function buildStackByColumns(displayCtrl, sCol) {
    const yCol = displayCtrl.getFirstColumnForAxis('y');
    const seen = new Set();
    displayCtrl.ndx.all().forEach((record) => {
      const value = sCol.renderValue(record[sCol.name]);
      if (value !== undefined && value !== null) {
        seen.add(value);
      }
    });
    return Array.from(seen).map((value) => ({
      name: value,
      label: value,
      valueAccessor: (g) => g.value[value] || 0,
      // No per-segment Data Label config exists in Stack By mode (segments
      // are discovered at render time, not declared columns) - defer to
      // the optional weight column's formatter if one was configured, else
      // pass the plain count through unchanged.
      renderValue: (v) => (yCol ? yCol.renderValue(v) : v),
    }));
  }

})(CRM.chart_kit.d3, CRM.chart_kit.dc, CRM.ts('chart_kit'));
