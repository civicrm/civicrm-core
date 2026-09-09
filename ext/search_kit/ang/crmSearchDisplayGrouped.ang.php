<?php
// Module for rendering Grouped List Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayGrouped.module.js',
    'ang/crmSearchDisplayGrouped/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayGrouped',
  ],
  'css' => [
    'css/crmSearchDisplayGrouped.css',
  ],
  'basePages' => [],
  'requires' => ['crmSearchDisplay', 'crmUi', 'crmSearchTasks'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-grouped' => 'E',
  ],
];
