(function (angular, $, _, dc, d3, crossfilter) {
  "use strict";

  angular.module('crmChartKit').component('crmSearchDisplayChartKit', {
    bindings: {
      apiEntity: '@',
      search: '<',
      display: '<',
      apiParams: '<',
      settings: '<',
      filters: '<',
      totalCount: '=?'
    },
    require: {
      afFieldset: '?^^afFieldset'
    },
    templateUrl: '~/crmChartKit/chartKitCanvas.html',
    controller: function ($scope, $element, searchDisplayBaseTrait, chartKitChartTypes, chartKitColumn) {
      const ts = $scope.ts = CRM.ts('chart_kit');

      // Mix in base display trait
      angular.extend(this, _.cloneDeep(searchDisplayBaseTrait));

      this.$onInit = () => {
        // run the initialiser from the base trait
        this.initializeDisplay($scope, $element);

        this.chartContainer = $('.crm-chart-kit-chart-container', $element)[0];

        // add our trait functions to the pre and post search hooks
        this.onPreRun.push(() => {
          // exit early if no chart type
          if (!this.initChartType()) {
            this.chartContainer.innerText = ts('No chart type');
            return;
          }

          this.chartContainer.innerHtml = '<div class="crm-loading-spinner"></div>';

          this.buildColumns();

          this.alwaysSortByDimAscending();
        });

        this.onPostRun.push(() => {
          // exit early if no chart type
          if (!this.initChartType()) {
            this.chartContainer.innerText = ts('No chart type');
            return;
          }

          this.renderChart();

          // trigger re-rendering as you edit settings
          // TODO: could this be quite js intensive on the client browser? should we make it optional?
          // TODO: get debounce to work?
          $scope.$watch('$ctrl.settings', this.onSettingsChange, true);
        });
      };

      this.getSortKeys = () => this.getDimensionColumns().map((col) => col.sourceKey);

      this.alwaysSortByDimAscending = () => {
        const sortKeys = this.getSortKeys();

        // stash a serialised string for quick checking in onSettingsChange
        this._currentSortKeys = sortKeys.join(',');
        // always sort the query by X axis - we can handle differently when we pass to d3
        // but this is the only way to get magic that the server knows about the order
        // (like option groups / month order etc)
        this.settings.sort = sortKeys.map((key) => [key, 'ASC']);
      };

      this.onSettingsChange = (newSettings, oldSettings) => {
        // just in case the chart type has been removed somehow
        if (!this.initChartType()) {
          this.chartContainer.innerText = ts('No chart type');
          return;
        }

        // force rebuild columns as they might have changed
        this.buildColumns();

        // if sort keys have changed, we need to re-run the search to get new ordering
        // from the server
        const newSortKeysSerialised = this.getSortKeys().join(',');
        if (this._currentSortKeys !== newSortKeysSerialised) {
          this.getResultsSoon();
        } else {
          // just rerender on the front end
          this.renderChart();
        }
      };

      // this provides the common render steps - which chart types can then hook
      // into at different points
      this.renderChart = () => {
        if (this.results.length === 0) {
          // show a no results type thing
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
      };

      this.initChartType = () => {
        // run initial settings through our legacy adaptor
        this.settings = chartKitChartTypes.legacySettingsAdaptor(this.settings);

        if (!this.settings.chartType) {
          this.chartContainer.innerText = ts('No chart type selected.');
          return false;
        }
        const type = chartKitChartTypes.types.find((type) => type.key === this.settings.chartType);
        if (!type || !type.service) {
          this.chartContainer.innerText = ts('No chart type selected.');
          return false;
        }
        this.chartType = type.service;
        return true;
      };

      this.buildCrossfilter = () => {
        const dataPoints = this.results.map((record, i) => {
          const dataPoint = {};

          this.getColumns().forEach((col) => {
            const resultValue = record.data[col.sourceKey];
            dataPoint[col.name] = col.applyParsers(resultValue);
          });

          return dataPoint;
        });

        this.ndx = crossfilter(dataPoints);
      };

      this.buildDimension = () => {
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
      };

      this.buildGroup = () => {

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
      };

      this.buildChart = () => {
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
          this.chart.cap(this.settings.maxSegments ? this.settings.maxSegments : null);
        }
        // load in ordering if implement by chart type
        if (this.chart.ordering) {
          this.chart.ordering(this.getOrderAccessor());
        }
      };

      this.buildCoordinateGrid = () => {
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
      };

      this.loadChartData = () => {
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
      };

      this.formatChart = () => {
        // provide title and label accessors based on our column config
        this.chart
          .title((d) => this.renderDataLabel(d, 'title'))
          .label((d) => this.renderDataLabel(d, 'label'));

        this.chart
          .width(() => (this.settings.format.width))
          .height(() => (this.settings.format.height))
          .on('pretransition', chart => {
            chart.selectAll('text').attr('fill', this.settings.format.labelColor);
            // we need to add the background here as well as to the containing div
            // in order for inclusion in exports
            chart.svg().style('background', this.settings.format.backgroundColor);
          });

        if (this.chartType.hasCoordinateGrid()) {
          this.formatCoordinateGrid();
        }

        if (this.chartType.showLegend && this.chartType.showLegend(this)) {
          this.addLegend();
        }
      };


      this.formatCoordinateGrid = () => {
        const xCol = this.getFirstColumnForAxis('x');

        // add ticks if not a date (dc is better at handling ticks for us for dates)
        if (xCol.scaleType !== 'date') {
          this.chart.xAxis().tickFormat((v) => xCol.renderValue(v));
        }

        this.chart.xAxisLabel(
          this.settings.format.xAxisLabel ? this.settings.format.xAxisLabel : xCol.label
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
          this.settings.format.yAxisLabel ? this.settings.format.yAxisLabel : leftYCols.map((col) => col.label).join(' - ')
        );

        if (supportsRightYAxis) {
          const rightYCols = allYCols.filter((col) => col.useRightAxis);
          if (rightYCols.length === 1) {
            this.chart.rightYAxis().tickFormat((v) => rightYCols[0].renderValue(v));
          }
          if (rightYCols) {
            this.chart.rightYAxisLabel(
              this.settings.format.rightYAxisLabel ? this.settings.format.rightYAxisLabel : rightYCols.map((col) => col.label).join(' - ')
            );
          }
        }

        // set gridline settings
        this.chart.renderVerticalGridLines(this.settings.format.xAxisGridlines);
        this.chart.renderHorizontalGridLines(this.settings.format.yAxisGridlines);

        this.chart
          .margins(this.settings.format.padding)
          .clipPadding(this.settings.format.padding.clip ? this.settings.format.padding.clip : 20);
      };

      this.addLegend = () => {
        const legend = dc.legend();

        if (this.chartType.legendTextAccessor) {
          legend.legendText(this.chartType.legendTextAccessor(this));
        }

        legend.highlightSelected(true);

        if (this.settings.showLegend === 'right') {
          // depends on chart type which padding keys are set
          // (potential bug: if you set right padding on an axis chart, then switch to a chart without a right axis setting,
          // it will keep using the right padding value, which you can no longer edit
          const rightPadding = this.settings.format.padding.right ? this.settings.format.padding.right : this.settings.format.padding.outer;
          legend.x(this.settings.format.width - legend.itemWidth() - rightPadding);
        }
        this.chart.legend(legend);

        // Correct vertical alignment of legend labels on Chrome
        // Should be fixed upstream and therefore unnecessary in DCv5
        this.chart.on('pretransition.legendTextCorrect', () =>
          this.chart.selectAll('.dc-legend-item text')
            .attr('y', legend.itemHeight() - 2)
        );
      };

      this.buildColumns = () => {
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
        this.columns = this.settings.columns
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
      };

      this.getColumns = () => this.columns;

      this.getDimensionColumns = () => this.getColumns().filter((col) => col.isDimension);

      this.getColumnsForAxis = (axisKey) => this.getColumns().filter((col) => col.axis === axisKey);

      this.getFirstColumnForAxis = (axisKey) => this.getColumns().find((col) => col.axis === axisKey);

      this.getOrderColumn = () => {
        const orderCol = this.getColumns().find((col) => col.isOrder);
        return orderCol ? orderCol : this.getFirstColumnForAxis('w');
      };

      this.getOrderDirection = () => (this.settings.chartOrderDir ? this.settings.chartOrderDir : 'ASC');

      this.getOrderAccessor = () => {
        const orderCol = this.getOrderColumn();
        if (!orderCol) {
          return ((d) => d);
        }
        const orderSign = (this.getOrderDirection() === 'ASC') ? 1 : -1;

        return ((d) => orderSign * orderCol.valueAccessor(d));
      };

      this.renderDataLabel = (dataPoint, maskContext = null, maskColsByName = null) => {
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
      };

      this.getCanvasStyle = () => {
        const formatSettings = this.settings.format ? this.settings.format : {};
        return {
          backgroundColor: formatSettings.backgroundColor,
          padding: formatSettings.padding.outer,
          display: 'inline-block',
        };
      };

      this.getContainerStyle = () => {
        const formatSettings = this.settings.format ? this.settings.format : {};
        return {
          height: formatSettings.height,
          width: formatSettings.width,
          margin: formatSettings.padding.inner,
        };
      };

      // build color scale integrating user-assigned colors
      this.buildColumnColorScale = (columns) => {

        // default color map based on column labels
        const defaultColors = d3.scaleOrdinal(columns.map((col) => col.label), dc.config.defaultColors());

        const finalColors = {};

        columns.forEach((col) => {
          // use user-assigned color or pick one from the default color scheme
          finalColors[col.label] = col.color ? col.color : defaultColors(col.label);
        });

        // mapping function from our dict
        return ((layerName) => finalColors[layerName]);
      };

      this.downloadImageUrl = (mime, url, ext) => {
        const filename = (this.settings.format.title ? this.settings.format.title : 'chart').replace(/[^a-zA-Z0-9-]+/g, '') + '.' + ext;
        const downloadLink = document.createElement('a');
        downloadLink.download = filename;
        downloadLink.href = url;
        downloadLink.downloadurl = [mime, downloadLink.download, url].join(':');
        this.chartContainer.append(downloadLink);
        downloadLink.click();
        this.chartContainer.removeChild(downloadLink);
      };

      this.getSvgData = () => {
        // get svg as base64 xml.
        const svg = this.chartContainer.querySelector('svg');
        const xml = new XMLSerializer().serializeToString(svg);
        return 'data:image/svg+xml;base64,' + btoa(xml);
      };

      this.downloadSVG = () => {
        this.downloadImageUrl('image/svg+xml', this.getSvgData(), 'svg');
      };

      this.downloadPNG = () => {
        const svgData = this.getSvgData();

        const canvas = document.createElement('canvas');
        canvas.width = this.settings.format.width;
        canvas.height = this.settings.format.height;

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
      };

    }
  });
})(angular, CRM.$, CRM._, CRM.chart_kit.dc, CRM.chart_kit.d3, CRM.chart_kit.crossfilter);
