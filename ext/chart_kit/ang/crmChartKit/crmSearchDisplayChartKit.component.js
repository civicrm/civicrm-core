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
        controller: function ($scope, $element, searchDisplayBaseTrait, chartKitChartTypes, chartKitReduceTypes) {
            const ts = $scope.ts = CRM.ts('chart_kit');

            // Mix in base display trait
            angular.extend(this, _.cloneDeep(searchDisplayBaseTrait));

            this.$onInit = () => {
                // run the initialiser from the base trait
                this.initializeDisplay($scope, $element);

                this.chartContainer = $('.crm-chart-kit-chart-container', $element)[0];

                // add our trait functions to the pre and post search hooks
                this.onPreRun.push(() => this.alwaysSortByXAscending());
                this.onPostRun.push(() => {
                    this.renderChart();
                    // trigger re-rendering as you edit settings
                    // TODO: could this be quite js intensive on the client browser? should we make it optional?
                    // TODO: get debounce to work?
                    $scope.$watch('$ctrl.settings', this.onSettingsChange, true);
                });
            };

            this.alwaysSortByXAscending = () => {
                this._currentSortKey = this.getXColumn().key;
                // always sort the query by X axis - we can handle differently when we pass to d3
                // but this is the only way to get magic that the server knows about the order
                // (like option groups / month order etc)
                this.sort = this.settings.sort = [[this._currentSortKey, 'ASC']];
            };

            this.onSettingsChange = (newSettings, oldSettings) => {
                // if X column key changes, we need to re-run the search to get new ordering
                // from the server
                if (newSettings.columns.find((col) => col.axis === 'x').key !== this._currentSortKey) {
                    this.getResultsPronto();
                } else {
                    // just rerender on the front end
                    this.renderChart();
                }
            };

            // this provides the common render steps - which chart types can then hook
            // into at different points
            this.renderChart = () => {
                if (!this.settings.chartType) {
                    this.chartContainer.innerText = ts('No chart type selected.');
                    return;
                }
                if (this.results.length === 0) {
                    // show a no results type thing
                    this.chartContainer.innerText = ts('Search returned no results.');
                    return;
                }
                this.initChartType();

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

                // run the dc render
                this.chart.render();
            };

            this.initChartType = () => {
                const type = chartKitChartTypes.find((type) => type.key === this.settings.chartType);
                this.chartType = type.service;
            };

            this.buildCrossfilter = () => {

                if (this.chartType.buildCrossfilter) {
                    this.chartType.buildCrossfilter(this);
                    return;
                }

                // place to store values from each categorical column in the order from the results
                // (which is useful for canonical ordering)
                this.categories = {};

                this.chartData = this.results.map((record, i) => this.getColumns().map((col) => {

                    let value = record.data[col.key];

                    switch (col.datePrecision) {
                        case 'year':
                            value = d3.timeYear.floor(Date.parse(value)).valueOf();
                            break;
                        case 'month':
                            value = d3.timeMonth.floor(Date.parse(value)).valueOf();
                            break;
                        case 'week':
                            value = d3.timeWeek.floor(Date.parse(value)).valueOf();
                            break;
                        case 'day':
                            value = d3.timeDay.floor(Date.parse(value)).valueOf();
                            break;
                        case 'hour':
                            value = d3.timeHour.floor(Date.parse(value)).valueOf();
                            break;
                    }

                    switch (col.scaleType) {
                        case 'categorical':
                            // initialise the category list for this column if it doesnt exist yet
                            if (!this.categories[col.index]) {
                                this.categories[col.index] = [];
                            }

                            const categoryIndex = this.categories[col.index].indexOf(value);

                            if (categoryIndex < 0) {
                                // if not found, add new category to our list
                                this.categories[col.index].push(value);
                                // we know its that last item in the list now
                                return this.categories[col.index].length - 1;
                            }

                            return categoryIndex;
                        default:
                            return value;
                    }
                }));

                this.ndx = crossfilter(this.chartData);
            };

            this.buildDimension = () => {

                if (this.chartType.buildDimension) {
                    this.chartType.buildDimension(this);
                    return;
                }

                // 99 times out of 100 the x axis will be column 0, but let's be sure
                // (assume there's only one x axis column)
                const xColumnIndex = this.getXColumn().index;
                this.dimension = this.ndx.dimension((d) => d[xColumnIndex]);
            };

            this.buildGroup = () => {

                if (this.chartType.buildGroup) {
                    this.chartType.buildGroup(this);
                    return;
                }

                const cols = this.getColumnsWithReducers();

                // reduce every coordinate using the functions from its column reduce type
                const reduceAdd = (p, v) => cols.map((col) => {
                    return col.reducer.add(p[col.index], v[col.index]);
                });
                const reduceSub = (p, v) => cols.map((col) => {
                    return col.reducer.sub(p[col.index], v[col.index]);
                });
                const reduceStart = () => cols.map((col) => {
                    return col.reducer.start();
                });

                this.group = this.dimension.group().reduce(reduceAdd, reduceSub, reduceStart);

                // find totals in each column
                this.columnTotals = this.ndx.groupAll().reduce(reduceAdd, reduceSub, reduceStart).value();
            };

            this.buildChart = () => {
                // use override from chart type if defined - otherwise use a default
                // based on chartType.getChartConstructor
                if (this.chartType.buildChart) {
                    this.chartType.buildChart(this);
                    return;
                }

                if (this.chartType.getChartConstructor) {
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

                    return;
                }

                throw new Error('Chart type should implement buildChart or getChartConstructor');
            };

            this.buildCoordinateGrid = () => {
                this.buildXAxis();
            };

            this.buildXAxis = () => {
                const xCol = this.getXColumn();
                const xDomainValues = this.columnTotals[xCol.index];
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
                        .valueAccessor(this.getValueAccessor(this.getColumnsForAxis('y')[0]));
                }
            };

            this.formatChart = () => {
                // provide title and label accessors based on our column config
                this.chart
                    .title((d) => this.renderDataLabel(d, this.dataPointLabelMask('title')(d)))
                    // svg doesn't render line breaks
                    .label((d) => this.renderDataLabel(d, this.dataPointLabelMask('label')(d)).replaceAll('\n', ' - '));
                //.label((d) => this.renderDataLabel(d, this.maskedDataPointValue(d, 'label')).split('\n').map(a => `<tspan>${a}</tspan>`).join(''));

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

                if (this.chartType.showLegend(this)) {
                    this.addLegend();
                }
            };


            this.formatCoordinateGrid = () => {
                // format x axis
                // add our label formatter to the tick values
                // EXCEPT for dates, where DC is much cleverer
                // than we are at adapting the date precision
                const xCols = this.getColumnsForAxis('x');

                if (xCols.length === 1) {

                    if (xCols[0].scaleType !== 'date') {
                        this.chart.xAxis().tickFormat((v) => this.renderDataValue(v, xCols[0]));
                    }
                }
                this.chart.xAxisLabel(
                    this.settings.format.xAxisLabel ? this.settings.format.xAxisLabel : xCols.map((col) => col.label).join(' - ')
                );

                // for Y axis, we need to work out whether this is split left and right
                const supportsRightYAxis = this.chart.rightYAxis;
                const allYCols = this.getColumnsForAxis('y');

                const leftYCols = supportsRightYAxis ? allYCols.filter((col) => !col.useRightAxis) : allYCols;

                // if only one y column, we can do fancy formatting for y ticks
                if (leftYCols.length === 1) {
                    this.chart.yAxis().tickFormat((v) => this.renderDataValue(v, leftYCols[0]));
                }
                this.chart.yAxisLabel(
                    this.settings.format.yAxisLabel ? this.settings.format.yAxisLabel : leftYCols.map((col) => col.label).join(' - ')
                );

                if (supportsRightYAxis) {
                    const rightYCols = allYCols.filter((col) => col.useRightAxis);
                    if (rightYCols.length === 1) {
                        this.chart.rightYAxis().tickFormat((v) => this.renderDataValue(v, rightYCols[0]));
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
                this.chart.on('pretransition', (chart) =>
                  chart.selectAll('.dc-legend-item text')
                    .attr('y', legend.itemHeight() - 2)
                );
            };

            // TODO: move everything from here down  to a util service?

            this.getColumns = () => this.settings.columns.map((col, colIndex) => {
                // we need the canonical column index to get data values
                col.index = colIndex;
                return col;
            }).filter((col) => col.key);

            this.getColumnsForAxis = (axisKey) => this.getColumns().filter((col) => col.axis === axisKey);

            /**
             * Get the reducer for a column, based on its reduceType key
             * ( defaults to returning the "list" reducer if reduceType isn't set )
             */
            this.getReducerForColumn = (col) => {
                if (col.reduceType) {
                  return chartKitReduceTypes.find((type) => type.key === col.reduceType);
                }
                return chartKitReduceTypes.find((type) => type.key === 'list');
            };

            this.getColumnsWithReducers = () => this.getColumns().map((col) => {
                col.reducer = this.getReducerForColumn(col);
                return col;
            });

            this.getXColumn = () => this.getColumnsForAxis('x')[0];

            this.getOrderColumn = () => this.getColumns()[parseInt(this.settings.chartOrderColIndex ? this.settings.chartOrderColIndex : 0)];

            this.getOrderDirection = () => (this.settings.chartOrderDir ? this.settings.chartOrderDir : 'ASC');

            this.getOrderAccessor = () => {
                const orderColValueAccessor = this.getValueAccessor(this.getOrderColumn());
                const orderSign = (this.getOrderDirection() === 'ASC') ? 1 : -1;

                return ((d) => orderSign * orderColValueAccessor(d));
            };

            this.getValueAccessor = (col) => ((d) => {
                const columnData = d.value[col.index];

                const reducer = this.getReducerForColumn(col);

                return reducer.final(columnData, this.columnTotals[col.index]);
            });


            this.dataPointLabelMask = (context) => ((dataPoint) => {
                const dataPointValue = this.dataPointValue(dataPoint);

                return this.getColumns().map((col) => {
                    if (col.dataLabelType === context) {
                        return dataPointValue[col.index];
                    }
                    return null;
                });
            });

            this.dataPointValue = (dataPoint) => {
                if (!dataPoint) {
                    return null;
                }
                // sometimes the data is in a sub-property
                // (depends on chartType or whether title or label ??)
                if (dataPoint.data) {
                    dataPoint = dataPoint.data;
                }
                if (dataPoint.value) {
                    dataPoint = dataPoint.value;
                }
                return dataPoint;
            };

            this.renderDataLabel = (dataPoint, dataPointValue) => {
                if (dataPoint.key === 'Others') {
                    return `${dataPoint.others.length} Others`;
                }
                return this.getColumns().map((col) => {
                    return this.renderColumnLabel(dataPointValue, col);
                })
                    // remove blanks
                    .filter((label) => !!label)
                    .join('\n');
            };

            this.renderColumnLabel = (dataPointValue, col) => {
                const value = this.renderColumnValue(dataPointValue, col);

                if (!value && value !== 0) {
                    return null;
                }

                if (col.dataLabelColumnPrefix) {
                    return col.label + ': ' + value;
                }

                return value;
            };

            this.renderColumnValue = (dataPointValue, col) => {
                const value = dataPointValue[col.index] ? dataPointValue[col.index] : null;

                if (!value && value !== 0) {
                    return null;
                }

                return this.renderReduceTypeValue(value, col);
            };

            this.renderReduceTypeValue = (value, col) => {
                const reducer = this.getReducerForColumn(col);

                value = reducer.final(value, this.columnTotals[col.index]);

                // list and percentage are special cases
                // for how we apply data value renderer
                switch (col.reduceType) {
                    case 'list':
                        // we need to apply the datavalue rendering to each element
                        return value.map((item) => this.renderDataValue(item, col)).join(', ');
                    case 'percentage_sum':
                    case 'percentage_count':
                        // TODO would we ever need to call renderDataValue here? before or after division?
                        const percentage = Math.floor(100 * value);
                        return `${percentage}%`;
                    default:
                        return this.renderDataValue(value, col);
                }
            };

            this.renderDataValue = (value, col) => {
                // convert timestamp crossfilter back to date string
                switch (col.scaleType) {
                    case 'categorical':
                        // convert categorical indexes back to label
                        value = this.categories[col.index][value];
                        break;
                }
                switch (col.datePrecision) {
                    case 'year':
                        value = new Date(value).toLocaleString(undefined, {year: 'numeric'});
                        break;
                    case 'month':
                        value = new Date(value).toLocaleString(undefined, {year: 'numeric', month: 'long'});
                        break;
                    case 'week':
                        value = new Date(value).toLocaleString(undefined, {year: 'numeric', month: 'long', day: 'numeric'});
                        break;
                    case 'day':
                        value = new Date(value).toLocaleString(undefined, {year: 'numeric', month: 'long', day: 'numeric'});
                        break;
                    case 'hour':
                        value = new Date(value).toLocaleString();
                        break;
                }
                switch (col.dataLabelFormatter) {
                    case 'round':
                        return value.toFixed(col.dataLabelDecimalPlaces);
                    case 'formatMoney':
                        return CRM.formatMoney(value, null, col.dataLabelMoneyFormatString);
                }
                return value;
            };

            this.getCanvasStyle = () => {
                const formatSettings = this.settings.format;
                return {
                    backgroundColor: formatSettings.backgroundColor,
                    padding: formatSettings.padding.outer,
                    display: 'inline-block',
                };
            };

            this.getContainerStyle = () => {
                const formatSettings = this.settings.format;
                return {
                    height: formatSettings.height,
                    width: formatSettings.width,
                    margin: formatSettings.padding.inner,
                };
            };

            this.buildColumnColorScale = (columns) => {
                // build color scale integrating user-assigned colors

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
