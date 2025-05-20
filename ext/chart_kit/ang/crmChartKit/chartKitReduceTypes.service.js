(function (angular, $, _) {
  "use strict";

  // Provides pluggable reducer for use in the chart_kit crossfilter
  // - see `buildGroup` function in `crmSearchDisplayChartKit`
  // - in the UI these are exposed as "Stat type"
  angular.module('crmChartKit').factory('chartKitReduceTypes', () => {

    const ts = CRM.ts('chart_kit');

    return [
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
        start: () => 0
      },
      {
        key: "percentage_count",
        label: ts("Percentage of count"),
        final: (f, total) => f / total,
        add: (p, v) => p + 1,
        sub: (p, v) => p - 1,
        start: () => 0
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
        start: () => []
      }
    ];
  });
})(angular, CRM.$, CRM._);
