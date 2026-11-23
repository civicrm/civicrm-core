(function (ts, dc, d3, crossfilter, chartKitChartTypes, chartKitTypeBackends, chartKitColumn, chartKitUtils, CiviSearchDisplay) {

  class CiviSearchDisplayChartKit extends CiviSearchDisplay {

    /* jshint ignore:start */
    static observedAttributes = ['filters', 'settings'];
    // Charts aggregate their rows, so the row count is not what they show
    static reportsRowCount = false;
    /* jshint ignore:end */

    constructor() {
      super();
    }

    connectedCallback() {
      super.connectedCallback();

      this.renderContainer();

      try {
        this.loadSettings();
      }
      catch (e) {
        this.chartContainer.innerText = e.message;
      }

      this.onPreRun.push(() => {
        if (!this.chartType) {
          return;
        }
        this.toggleLoading(true);
      });

      this.onPostRun.push(() => {
        if (!this.chartType) {
          return;
        }
        this.renderChart();
      });
    }

    disconnectedCallback() {
      super.disconnectedCallback();
    }

    attributeChangedCallback(name, oldValue, newValue) {
      super.attributeChangedCallback(name, oldValue, newValue);

      if (name === 'settings') {
        this.reloadSoon();
      }
    }

    get title() {
      return (this._settings && this._settings.format) ? this._settings.format.title : null;
    }

    toggleLoading(loading = true) {
      this.chartContainer?.classList.toggle('crm-search-loading-placeholder', loading);
    }

    renderContainer() {
      this.innerHTML = `
        <div class="crm-chart-kit-canvas">

          <div class="crm-chart-kit-chart-title"></div>

          <div class="crm-chart-kit-chart-container"></div>

          <div class="crm-chart-kit-download-links"></div>
        </div>
      `;
    }

    renderDownloadLinks() {
      if (this._settings && this._settings.showDownloadLinks) {
        this.querySelector('.crm-chart-kit-download-links').innerHTML = `
          <a class="btn btn-sm crm-chart-kit-download-svg" onclick="this.closest('civi-search-display-chart-kit').downloadSVG()">
            ${ts('Download (SVG)')}
          </a>
          <a class="btn btn-sm crm-chart-kit-download-png" onclick="this.closest('civi-search-display-chart-kit').downloadPNG()">
            ${ts('Download (PNG)')}
          </a>
        `;
      }
    }

    renderTitle() {
      if (this.title) {
        this.titleContainer.innerText = this.title;
        this.titleContainer.style.display = null;
      }
      else {
        this.titleContainer.style.display = 'none';
      }
    }

    get chartContainer() {
      return this.querySelector('.crm-chart-kit-chart-container');
    }

    get titleContainer() {
      return this.querySelector('.crm-chart-kit-chart-title');
    }

    setStyles() {
      this.setContainerStyles();
      this.setCanvasStyles();
      if (this.titleContainer) {
        this.titleContainer.style.color = (this._settings && this._settings.format) ? this._settings.format.labelColor : null;
      }
    }

    getSortKeys() {
      return this.getDimensionColumns().map((col) => col.sourceKey);
    }

    alwaysSortByDimAscending() {
      const sortKeys = this.getSortKeys();

      // always sort the query by X axis - we can handle differently when we pass to d3
      // but this is the only way to get magic that the server knows about the order
      // (like option groups / month order etc)
      this.sort = sortKeys.map((key) => [key, 'ASC']);
    }

    reloadSoon() {
      this.toggleLoading(true);
      clearTimeout(this.nextReload);
      this.nextReload = setTimeout(() => this.reload(), 500);
    }

    reload() {
      // before reloading the settings, take a note of what has previously been fetched
      // from the server, so we know whether a refetch is required
      const previousCols = this.getColumns().map((col) => col.key).join(',');
      const previousSort = this.getSortKeys().join(',');

      try {
        this.loadSettings();
      }
      catch (e) {
        this.toggleLoading(false);
        this.chartContainer.innerText = e.message;
        // if error loading settings, go no further
        return;
      }

      // if sort keys have changed, we need to re-run the search to get new ordering
      const newCols = this.getColumns().map((col) => col.key).join(',');
      const newSort = this.getSortKeys().join(',');
      if (!this.results || newCols !== previousCols || newSort !== previousSort) {
        this.getResultsSoon();
      } else {
        // we can just rerender on the clientside
        this.renderChart();
      }
    }

    // this provides the common render steps - which chart types can then hook
    // into at different points
    renderChart() {
      if (this.results.length === 0) {
        // show a no results message
        this.toggleLoading(false);
        this.chartContainer.innerText = ts('Search returned no results.');
        return;
      }

      // loads search results data into crossfilter
      this.buildCrossfilter();

      // adds dimension to the crossfilter
      this.buildDimension();

      // adds group to the crossfilter
      this.buildGroup();

      // creates the dc chart object
      this.buildChart();

      // loads the crossfilter into the chart object
      this.loadChartData();

      // apply formattting
      this.formatChart();

      this.chartContainer.innerText = '';
      // run the dc render
      this.chart.render();
      this.renderDownloadLinks();
      this.toggleLoading(false);
    }

    loadSettings() {
      if (!Object.keys(this.settings).length) {
        // ignore empty settings - this happens more than you'd like
        // because of the angular wrapper
        return;
      }

      this._settings = chartKitUtils.legacySettingsAdaptor(this.settings);

      this.initChartType();

      this.buildColumns();

      this.alwaysSortByDimAscending();

      this.renderTitle();

      this.setStyles();
    }

    initChartType() {
      const key = this._settings.chartType;
      const type = chartKitChartTypes.find((type) => type.key === key);
      if (!type) {
        throw new Error(ts('No chart type selected'));
      }
      this.chartType = chartKitTypeBackends[type.backend];
      if (!this.chartType) {
        throw new Error(ts('Chart type backend not found'));
      }
    }

    buildCrossfilter() {
      const dataPoints = this.results.map((record, i) => {
        const dataPoint = {};

        this.getColumns().forEach((col) => {
          const resultValue = record.data[col.sourceKey];
          dataPoint[col.name] = col.applyParsers(resultValue);
        });

        return dataPoint;
      });

      this.ndx = crossfilter(dataPoints);
    }

    buildDimension() {
      const colNames = this.getDimensionColumns().map((col) => col.name);

      if (colNames.length > 1) {
        // dimension is multi-column, create an array key
        this.dimension = this.ndx.dimension((d) => colNames.map((i) => d[i]));
      }
      else {
        // if there is only one dimension axis we use the actual value
        // rather than a single item array
        const colName = colNames[0];
        this.dimension = this.ndx.dimension((d) => d[colName]);
      }
    }

    buildGroup() {

      if (this.chartType.buildGroup) {
        this.chartType.buildGroup(this);
        return;
      }

      // reduce every coordinate using the functions from its column reduce type
      const reduceAdd = (p, v) => {
        this.getColumns().forEach((col) => {
          p[col.name] = col.reducer.add(p[col.name], v[col.name]);
        });
        return p;
      };
      const reduceSub = (p, v) => {
        this.getColumns().forEach((col) => {
          p[col.name] = col.reducer.sub(p[col.name], v[col.name]);
        });
        return p;
      };
      const reduceStart = () => {
        const p = {};
        this.getColumns().forEach((col) => {
          p[col.name] = col.reducer.start();
        });
        return p;
      };

      this.group = this.dimension.group().reduce(reduceAdd, reduceSub, reduceStart);

      // find grand totals for each column
      const columnTotals = this.ndx.groupAll().reduce(reduceAdd, reduceSub, reduceStart).value();

      this.getColumns().forEach((col) => col.setTotal(columnTotals[col.name]));
    }

    buildChart() {
      // use override from chart type if defined - otherwise use a default
      // based on chartType.getChartConstructor
      if (this.chartType.buildChart) {
        this.chartType.buildChart(this);
        return;
      }

      if (!this.chartType.getChartConstructor) {
        throw new Error('Chart type should implement buildChart or getChartConstructor');
      }

      this.chart = this.chartType.getChartConstructor(this)(this.chartContainer);

      if (this.chartType.hasCoordinateGrid()) {
        this.buildCoordinateGrid();
      }

      // load in cap if implemented by chart type
      if (this.chart.cap) {
        this.chart.cap(this._settings.maxSegments ? this._settings.maxSegments : null);
      }
      // load in ordering if implement by chart type
      if (this.chart.ordering) {
        this.chart.ordering(this.getOrderAccessor());
      }
    }

    buildCoordinateGrid() {
      const xCol = this.getFirstColumnForAxis('x');

      const xDomainValues = xCol.total;
      const min = Math.min(...xDomainValues);
      const max = Math.max(...xDomainValues);

      switch (xCol.scaleType) {
        case 'date':
          // timescale
          this.chart.x(d3.scaleTime().domain([min, max]).nice());
          break;

        case 'categorical':
          this.chart
            .x(d3.scaleBand().domain(xDomainValues))
            .xUnits(dc.units.ordinal);
          break;

        case 'integer':
          this.chart
            .x(d3.scaleBand().domain(d3.range(min, max + 1)))
            .xUnits(dc.units.ordinal);
          break;

        default:
          // regular linear scale
          this.chart.x(d3.scaleLinear().domain([min, max]).nice());
          break;
      }

      this.chart
        // the brush is supposed to provide filtering
        // if we could pass that back up to the search kit filters
        // that would be amazing but very non-trivial
        .brushOn(false)
        .mouseZoomable(xCol.scaleType !== 'categorical');
    }

    loadChartData() {
      // use override from the chart type if defined
      // otherwise use a default that works for simple charts
      if (this.chartType.loadChartData) {
        this.chartType.loadChartData(this);
      } else {
        this.chart
          .dimension(this.dimension)
          .group(this.group)
          // default value is just the first y co-ordinate
          .valueAccessor((d) => this.getFirstColumnForAxis('y').valueAccessor(d));
      }
    }

    formatChart() {
      // provide title and label accessors based on our column config
      this.chart
        .title((d) => this.renderDataLabel(d, 'title'))
        .label((d) => this.renderDataLabel(d, 'label'));

      this.chart
        .width(() => (this._settings.format.width))
        .height(() => (this._settings.format.height))
        .on('pretransition', () => {
          // we need to add the background here as well as to the containing div
          // in order for inclusion in exports
          this.chart.svg().style('background', this._settings.format.backgroundColor);
          this.chart.selectAll('text').attr('fill', this._settings.format.labelColor);
        });

      if (this.chartType.hasCoordinateGrid()) {
        this.formatCoordinateGrid();
      }

      if (this.chartType.showLegend && this.chartType.showLegend(this)) {
        this.addLegend();
      }
    }

    formatCoordinateGrid() {
      const xCol = this.getFirstColumnForAxis('x');

      // add ticks if not a date (dc is better at handling ticks for us for dates)
      if (xCol.scaleType !== 'date') {
        this.chart.xAxis().tickFormat((v) => xCol.renderValue(v));
      }

      this.chart.xAxisLabel(
        this._settings.format.xAxisLabel ? this._settings.format.xAxisLabel : xCol.label
        // TODO: could we have multi-x?
        //this.settings.format.xAxisLabel ? this.settings.format.xAxisLabel : xCols.map((col) => col.label).join(' - ')
      );

      // for Y axis, we need to work out whether this is split left and right
      const supportsRightYAxis = this.chart.rightYAxis;
      const allYCols = this.getColumnsForAxis('y');

      const leftYCols = supportsRightYAxis ? allYCols.filter((col) => !col.useRightAxis) : allYCols;

      // if only one y column, we can do fancy formatting for y ticks
      if (leftYCols.length === 1) {
        this.chart.yAxis().tickFormat((v) => leftYCols[0].renderValue(v));
      }
      this.chart.yAxisLabel(
        this._settings.format.yAxisLabel ? this._settings.format.yAxisLabel : leftYCols.map((col) => col.label).join(' - ')
      );

      if (supportsRightYAxis) {
        const rightYCols = allYCols.filter((col) => col.useRightAxis);
        if (rightYCols.length === 1) {
          this.chart.rightYAxis().tickFormat((v) => rightYCols[0].renderValue(v));
        }
        if (rightYCols) {
          this.chart.rightYAxisLabel(
            this._settings.format.rightYAxisLabel ? this._settings.format.rightYAxisLabel : rightYCols.map((col) => col.label).join(' - ')
          );
        }
      }

      // set gridline settings
      this.chart.renderVerticalGridLines(this._settings.format.xAxisGridlines);
      this.chart.renderHorizontalGridLines(this._settings.format.yAxisGridlines);

      this.chart
        .margins(this._settings.format.padding)
        .clipPadding(this._settings.format.padding.clip ? this._settings.format.padding.clip : 20);
    }

    addLegend() {
      const legend = dc.legend();

      if (this.chartType.legendTextAccessor) {
        legend.legendText(this.chartType.legendTextAccessor(this));
      }

      legend.highlightSelected(true);

      if (this._settings.showLegend === 'right') {
        this.placeRightLegend(legend);
      }
      this.chart.legend(legend);

      // Correct vertical alignment of legend labels on Chrome
      // Should be fixed upstream and therefore unnecessary in DCv5
      this.chart.on('pretransition.legendTextCorrect', () =>
        this.chart.selectAll('.dc-legend-item text')
          .attr('y', legend.itemHeight() - 2)
      );
    }

    // Place a right-hand legend in the margin beside the plot rather than on top of the
    // data, and size that space to the legend itself instead of a hand-tuned
    // format.padding.right per report. After each render we measure the widest legend
    // item and park the legend so it never overlaps the plotted data (to its left) or
    // clips at the canvas edge (to its right) — however many entries it has or however
    // long the labels are.
    placeRightLegend(legend) {
      const leftGap = 16;   // space between the plot (or its right axis) and the legend
      const rightGap = 12;  // space between the legend and the canvas's right edge
      const minRight = this._settings.format.padding.outer || 10;
      const maxWidth = this._settings.format.legendMaxWidth || 180;

      // First-paint guess, corrected by the fit handler once the legend DOM can be measured.
      legend.x(this._settings.format.width - legend.itemWidth() - rightGap);
      legend.y(this._settings.format.padding.top || 0);

      const isGrid = this.chartType.hasCoordinateGrid();

      this.chart.on('pretransition.ckLegendFit', (chart) => {
        const svg = chart.svg();
        if (!svg || svg.empty()) {
          return;
        }
        // Wrap long labels to maxWidth and re-stack the items; returns the bounded legend
        // width (widest wrapped line plus the swatch offset).
        const legendBand = this.wrapRightLegend(svg, maxWidth);
        if (!legendBand) {
          return;
        }

        // Charts without a coordinate grid (e.g. pie) have no margins() to reserve — the plot
        // is centred in the canvas, so a right legend would sit on top of it. Right-align the
        // now-bounded legend, then centre the pie in the space to its left and fit the radius
        // to that space so the two don't overlap. (Calling chart.margins() would throw here.)
        if (!isGrid) {
          const top = this._settings.format.padding.top || 0;
          const canvasW = chart.width();
          const canvasH = chart.height();
          const legendX = canvasW - legendBand - rightGap;
          svg.select('.dc-legend').attr('transform', `translate(${legendX},${top})`);

          // Only pie-type charts expose cx()/radius(); if they're missing, just place the
          // legend and leave the plot alone.
          if (typeof chart.cx === 'function' && typeof chart.radius === 'function') {
            const availWidth = legendX - leftGap;
            const newCx = Math.round(availWidth / 2);
            const newCy = Math.round(canvasH / 2);
            const newRadius = Math.max(20, Math.floor(Math.min(availWidth, canvasH) / 2) - 10);
            const cxNow = chart.cx() || Math.round(canvasW / 2);
            const rNow = chart.radius() || 0;
            // Re-centre/resize once, guarded by a threshold so it settles instead of looping.
            if (Math.abs(cxNow - newCx) > 2 || Math.abs(rNow - newRadius) > 2) {
              chart.cx(newCx);
              chart.cy(newCy);
              chart.radius(newRadius);
              chart.redraw();
            }
          }
          return;
        }

        // A composite chart can carry a right-hand Y axis. dc draws its ticks just outside
        // the plot's right edge and pins its label hard against the canvas edge, so the
        // legend has to clear both: reserve a band for the (measured) tick width to the
        // left of the legend, and keep a strip free at the far right for the label.
        const rightAxis = svg.select('g.axis.yr');
        const hasRightAxis = !rightAxis.empty();
        const axisBand = hasRightAxis ? Math.ceil(rightAxis.node().getBBox().width) : 0;
        const hasRightAxisLabel = hasRightAxis &&
          typeof chart.rightYAxisLabel === 'function' &&
          chart.rightYAxisLabel();
        const labelStrip = hasRightAxisLabel ? 24 : 0;

        const neededRight = Math.max(minRight, axisBand + leftGap + legendBand + labelStrip + rightGap);
        const top = chart.margins().top || 0;
        const x = chart.width() - legendBand - labelStrip - rightGap;

        legend.x(x);
        legend.y(top);
        svg.select('.dc-legend').attr('transform', `translate(${x},${top})`);

        // Re-fit the plot margin to what we measured, then redraw once. The threshold stops
        // it looping on sub-pixel differences and lets a later legend change (e.g. a filter)
        // re-fit on its own.
        if (Math.abs((chart.margins().right || 0) - neededRight) > 2) {
          chart.margins(Object.assign({}, chart.margins(), { right: neededRight }));
          chart.redraw();
        }
      });
    }

    // Wrap each right-legend label onto multiple lines at a maximum width and re-stack the
    // items vertically. dc lays legend items out single-line at a fixed row height, and SVG
    // <text> does not wrap on its own, so we rebuild each label as stacked <tspan> lines and
    // recompute each item's vertical offset (wrapped items are taller). Returns the widest
    // wrapped line plus the swatch offset — the bounded width the caller reserves.
    wrapRightLegend(svg, maxWidth) {
      const items = svg.selectAll('.dc-legend-item').nodes();
      if (!items.length) {
        return 0;
      }
      const svgNs = 'http://www.w3.org/2000/svg';
      const lineHeight = 13;
      const rowGap = 6;

      // Measure text with a canvas using the legend's own font — cheap, and no DOM reflow per
      // word the way repeatedly setting textContent + getComputedTextLength would cost.
      const firstText = items[0].querySelector('text');
      const font = firstText ? window.getComputedStyle(firstText).font : '';
      this._legendMeasureCtx = this._legendMeasureCtx || document.createElement('canvas').getContext('2d');
      const ctx = this._legendMeasureCtx;
      ctx.font = font || '11px sans-serif';
      const widthOf = (s) => ctx.measureText(s).width;

      let runningY = 0;
      let widest = 0;
      let textOffset = 14;

      items.forEach((g) => {
        const textEl = g.querySelector('text');
        if (!textEl) {
          return;
        }
        textOffset = +(textEl.getAttribute('x') || 14);
        const full = (textEl.textContent || '').trim();

        // Greedy word wrap to maxWidth (a single word longer than maxWidth stays on its own
        // line rather than being split mid-word).
        const words = full.split(/\s+/);
        const lines = [];
        let cur = '';
        words.forEach((w) => {
          const trial = cur ? `${cur} ${w}` : w;
          if (cur && widthOf(trial) > maxWidth) {
            lines.push(cur);
            cur = w;
          }
          else {
            cur = trial;
          }
        });
        if (cur) {
          lines.push(cur);
        }
        if (!lines.length) {
          lines.push(full);
        }

        // Rebuild the <text> as stacked <tspan> lines.
        while (textEl.firstChild) {
          textEl.removeChild(textEl.firstChild);
        }
        lines.forEach((line, i) => {
          const ts = document.createElementNS(svgNs, 'tspan');
          ts.setAttribute('x', textOffset);
          ts.setAttribute('dy', i === 0 ? '0' : String(lineHeight));
          ts.textContent = line;
          textEl.appendChild(ts);
          const lw = widthOf(line);
          if (lw > widest) {
            widest = lw;
          }
        });

        // Re-stack: position this item below the previous one by its wrapped height.
        g.setAttribute('transform', `translate(0,${runningY})`);
        runningY += Math.max(12, lines.length * lineHeight) + rowGap;
      });

      return Math.ceil(textOffset + widest);
    }

    buildColumns() {
      if (!this.chartType) {
        return;
      }

      const axes = this.chartType.getAxes();

      // TODO: set maxColumns directly rather than multiColumn
      Object.keys(axes).forEach((axisKey) => {
        axes[axisKey].maxColumns = axes[axisKey].multiColumn ? -1 : 1;
      });

      const countByAxis = {};

      // get column settings for the display
      this.columns = this._settings.columns
        // filter columns with no source column set
        .filter((col) => col.key)
        .map((col) => {
          const axis = axes[col.axis];

          // skip columns with unrecognised axis keys
          if (axis === undefined) {
            return null;
          }

          // check next index for this axis. if we havent seen this axis before, start at 0
          const axisIndex = countByAxis[col.axis] ? countByAxis[col.axis] : 0;

          // if limit is not -1, and next index exceeds the limit for the axis, skip this column
          if ((0 <= axis.maxColumns) && (axis.maxColumns <= axisIndex)) {
            return null;
          }

          col.name = `${col.axis}_${axisIndex}`;

          col.isDimension = axis.isDimension ? axis.isDimension : false;

          // increment the counter
          countByAxis[col.axis] = axisIndex + 1;

          return col;
        })
        // remove null columns
        .filter((col) => col)
        // sort by name (which sorts by axis)
        .sort((a, b) => a.name > b.name)
        // initialise each ChartKitColumn object
        .map((col) => new chartKitColumn(
              col.name,
              col.axis,
              col.key,
              col
        ));
    }

    getColumns() {
      return this.columns ? this.columns : [];
    }

    getDimensionColumns() {
      return this.getColumns().filter((col) => col.isDimension);
    }

    getColumnsForAxis(axisKey) {
      return this.getColumns().filter((col) => col.axis === axisKey);
    }

    getFirstColumnForAxis(axisKey) {
      return this.getColumns().find((col) => col.axis === axisKey);
    }

    getOrderColumn() {
      const orderCol = this.getColumns().find((col) => col.isOrder);
      return orderCol ? orderCol : this.getFirstColumnForAxis('w');
    }

    getOrderDirection() {
      return this._settings.chartOrderDir ? this._settings.chartOrderDir : 'ASC';
    }

    getOrderAccessor() {
      const orderCol = this.getOrderColumn();
      if (!orderCol) {
        return ((d) => d);
      }
      const orderSign = (this.getOrderDirection() === 'ASC') ? 1 : -1;

      return ((d) => orderSign * orderCol.valueAccessor(d));
    }

    renderDataLabel(dataPoint, maskContext = null, maskColsByName = null) {
      if (!dataPoint || dataPoint.key === 'empty') {
        return null;
      }
      if (dataPoint.key === 'Others') {
        return `${dataPoint.others.length} Others`;
      }
      // sometimes the data point value is below  "data" subkey
      // (depends on chartType or whether title or label ??)
      if (!dataPoint.value && dataPoint.data) {
        dataPoint = dataPoint.data;
      }

      let columns = this.getColumns();

      if (maskContext) {
        columns = columns.filter((col) => col.dataLabelType === maskContext);
      }
      if (maskColsByName) {
        columns = columns.filter((col) => !maskColsByName.includes());
      }

      return columns.map((col) => col.getRenderedLabel(dataPoint))
        // remove blanks
        .filter((label) => !!label)
        .join(' - ');
    }

    get chartCanvas() {
      return this.querySelector('.crm-chart-kit-canvas');
    }

    setCanvasStyles() {
      const formatSettings = this._settings.format ? this._settings.format : {};

      if (formatSettings.labelColor) {
        this.chartCanvas.style.setProperty('--crm-text-color', formatSettings.labelColor);
      }
      this.chartCanvas.style.backgroundColor = formatSettings.backgroundColor;
      this.chartCanvas.style.padding = formatSettings.padding ? formatSettings.padding.outer : null;
      this.chartCanvas.style.display = 'inline-block';
    }

    setContainerStyles() {
      const formatSettings = this._settings.format ? this._settings.format : {};
      this.chartContainer.style.height = formatSettings.height ? `${formatSettings.height}px` : null;
      this.chartContainer.style.width = formatSettings.width ? `${formatSettings.width}px` : null;
      this.chartContainer.style.margin = formatSettings.padding ? formatSettings.padding.inner : null;
    }

    // build color scale integrating user-assigned colors
    buildColumnColorScale(columns) {

      // default color map based on column labels
      const defaultColors = d3.scaleOrdinal(columns.map((col) => col.label), dc.config.defaultColors());

      const finalColors = {};

      columns.forEach((col) => {
        // use user-assigned color or pick one from the default color scheme
        finalColors[col.label] = col.color ? col.color : defaultColors(col.label);
      });

      // mapping function from our dict
      return ((layerName) => finalColors[layerName]);
    }

    downloadImageUrl(mime, url, ext) {
      const filename = (this._settings.format.title ? this._settings.format.title : 'chart').replace(/[^a-zA-Z0-9-]+/g, '') + '.' + ext;
      const downloadLink = document.createElement('a');
      downloadLink.download = filename;
      downloadLink.href = url;
      downloadLink.downloadurl = [mime, downloadLink.download, url].join(':');
      this.chartContainer.append(downloadLink);
      downloadLink.click();
      this.chartContainer.removeChild(downloadLink);
    }

    getSvgData() {
      // get svg as base64 xml.
      const svg = this.chartContainer.querySelector('svg');
      const xml = new XMLSerializer().serializeToString(svg);
      return 'data:image/svg+xml;base64,' + btoa(xml);
    }

    downloadSVG() {
      this.downloadImageUrl('image/svg+xml', this.getSvgData(), 'svg');
    }

    downloadPNG() {
      const svgData = this.getSvgData();

      const canvas = document.createElement('canvas');
      canvas.width = this._settings.format.width;
      canvas.height = this._settings.format.height;

      this.chartContainer.append(canvas);

      const img = document.createElement('img');
      img.onload = () => {
        canvas.getContext('2d').drawImage(img, 0, 0);
        // canvas.style.display = 'block';
        const imgURL = canvas.toDataURL('image/png');
        this.downloadImageUrl('image/png', imgURL, 'png');
        this.chartContainer.removeChild(canvas);
      };
      img.src = svgData;
    }
  }

  customElements.define('civi-search-display-chart-kit', CiviSearchDisplayChartKit);

})(CRM.ts('chart_kit'), CRM.chart_kit.dc, CRM.chart_kit.d3, CRM.chart_kit.crossfilter, CRM.chart_kit.chartTypes, CRM.chart_kit.typeBackends, CRM.chart_kit.column, CRM.chart_kit.utils, CRM.components.civi_search_display);
