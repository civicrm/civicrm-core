<?php
// Module for rendering Chart search displays
return [
  'js' => [
    'ang/crmChartKit.js',
    'ang/crmChartKit/*.js',
  ],
  'css' => [
    'ang/crmChartKit.css',
  ],
  'partials' => [
    'ang/crmChartKit',
  ],
  'requires' => [
    'crmUi',
    'crmUtil',
    'ngRoute',
    'ui.bootstrap',
    'crmSearchDisplay',
  ],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'bundles' => ['bootstrap3', 'chart_kit'],
  'exports' => [
    'crm-search-display-chart-kit' => 'E',
  ],
  'settings' => [],
];
