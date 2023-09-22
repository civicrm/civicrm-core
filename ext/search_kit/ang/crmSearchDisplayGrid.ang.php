<?php
// Module for rendering Grid Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayGrid.module.js',
    'ang/crmSearchDisplayGrid/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayGrid',
  ],
  'css' => [
    'css/crmSearchDisplayGrid.css',
  ],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'requires' => ['crmSearchDisplay', 'crmUi', 'ui.bootstrap', 'crmSearchTasks'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-grid' => 'E',
  ],
];
