<?php
// Module for administering Chart search displays
return [
  'js' => [
    'ang/crmChartKitAdmin.js',
    'ang/crmChartKitAdmin/*.js',
  ],
  'css' => [
    'ang/crmChartKitAdmin.css',
  ],
  'partials' => [
    'ang/crmChartKitAdmin',
    'ang/crmChartKitAdmin/chartTypes',
  ],
  'requires' => [
    'crmSearchAdmin',
    'crmChartKit',
  ],
  'basePages' => ['civicrm/admin/search'],
];
