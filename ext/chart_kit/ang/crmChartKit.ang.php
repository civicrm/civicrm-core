<?php
// Base angular module declares external dependencies

return [
  'js' => [
    'ang/crmChartKit.js',
    'ang/crmChartKit/*.js',
    'ang/crmChartKit/chartTypes/*.js',
  ],
  'css' => [
    'ang/crmChartKit.css',
  ],
  'partials' => [
    'ang/crmChartKit',
    'ang/crmChartKit/chartTypes',
  ],
  'requires' => [
    'crmUi',
    'crmUtil',
    'ngRoute',
    'ui.bootstrap',
    'crmSearchDisplay',
  ],
  'basePages' => ['civicrm/admin/search'],
  'bundles' => ['bootstrap3', 'chart_kit'],
  'exports' => [
    'crm-search-display-chart-kit' => 'E',
  ],
  'settings' => [],
];
