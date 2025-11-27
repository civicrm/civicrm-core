(function (dc, d3) {
  CRM.chart_kit = CRM.chart_kit || {};

  CRM.chart_kit.typeBackends = CRM.chart_kit.typeBackends || {};

  CRM.chart_kit.typeBackends.heatmap = {
    adminTemplate: '~/crmChartKitAdmin/typeBackends/chartKitHeatMapAdmin.html',

    getAxes: () => ({
      'w': {
        label: ts('Columns'),
        reduceTypes: ['list'],
        scaleTypes: ['categorical'],
        dataLabelTypes: ['title', 'label', 'none'],
        multiColumn: true,
        isDimension: true,
      },
      'v': {
        label: ts('Rows'),
        reduceTypes: ['list'],
        scaleTypes: ['categorical'],
        dataLabelTypes: ['title', 'label', 'none'],
        isDimension: true,
      },
      'y': {
        label: ts('Color'),
        dataLabelTypes: ['title', 'label', 'none'],
      },
      'z': {
        label: ts('Additional labels'),
        dataLabelTypes: ['label', 'title'],
        prepopulate: false,
        multiColumn: true,
      }
    }),

    hasCoordinateGrid: () => false,

    getInitialDisplaySettings: () => ({
      colorScaleMin: '#91223c',
      colorScaleMax: '#2e562e',
    }),

    getChartConstructor: () => dc.heatMap,

    loadChartData: (displayCtrl) => {
      const colColumn = displayCtrl.getFirstColumnForAxis('w');
      const rowColumn = displayCtrl.getFirstColumnForAxis('v');
      const colorColumn = displayCtrl.getFirstColumnForAxis('y');

      displayCtrl.chart
        .dimension(displayCtrl.dimension)
        .group(displayCtrl.group)
        .keyAccessor((d) => colColumn.valueAccessor(d), colColumn.label)
        .colsLabel((d) => colColumn.renderValue(d[0]))
        .colOrdering((a, b) => d3.ascending(a[0], b[0]))
        .valueAccessor((d) => rowColumn.valueAccessor(d), rowColumn.label)
        .rowsLabel((d) => rowColumn.renderValue(d[0]))
        .rowOrdering((a, b) => {
          return d3.ascending(rowColumn.renderValue(a), rowColumn.renderValue(b));
        });

      // set up color scale
      displayCtrl.chart
        .colorAccessor((d) => colorColumn.valueAccessor(d))
        .calculateColorDomain();
      const colorScale = d3.scaleLinear(displayCtrl.chart.colorDomain(), [displayCtrl._settings.colorScaleMin, displayCtrl._settings.colorScaleMax]);
      displayCtrl.chart.colors(colorScale);


      // add labels
      displayCtrl.chart
        .on('renderlet', () => {
          // add additional text box to each heatbox
          const boxGroups = displayCtrl.chart.selectAll('.box-group');

          // remove any existing labels to avoid duplication
          boxGroups.selectAll('text.heat-box-label').remove();

          // regen label text node for each box group
          boxGroups.append(function (d) {
            const rect = this.querySelector('rect.heat-box');
            const getFloatAttrFromRect = (attr) => parseFloat(rect.getAttribute(attr));
            const x = getFloatAttrFromRect('x') + getFloatAttrFromRect('width') / 2;
            const y = getFloatAttrFromRect('y') + getFloatAttrFromRect('height') / 2;

            const label = document.createElementNS('http://www.w3.org/2000/svg', 'text');
            label.classList.add('heat-box-label');
            label.setAttribute('x', x);
            label.setAttribute('y', y);
            label.setAttribute('fill', displayCtrl._settings.format.labelColor);
            label.style.textAnchor = 'middle';

            return label;
          });

          // assign the text content
          boxGroups.select('.heat-box-label').text((d) => displayCtrl.renderDataLabel(d, 'label').replaceAll('\n', ' - '));
        });
    }
  };
})(CRM.chart_kit.dc, CRM.chart_kit.d3);

