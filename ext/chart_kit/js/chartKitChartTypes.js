(function (d3, dc, ts) {

  CRM.chart_kit = CRM.chart_kit || {};

  /**
   * Chart types exposed in editor - roughly corresponds
   * to different rendering backends
   */
  CRM.chart_kit.chartTypes = [
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

})(CRM.chart_kit.d3, CRM.chart_kit.dc, CRM.ts('chart_kit'));
