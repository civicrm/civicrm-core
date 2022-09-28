<?php
// Module for rendering Table Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayTable.module.js',
    'ang/crmSearchDisplayTable/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayTable',
  ],
  'css' => [
    'css/crmSearchDisplayTable.css',
  ],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'requires' => ['crmSearchDisplay', 'crmUi', 'crmSearchTasks', 'ui.bootstrap', 'ui.sortable'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-table' => 'E',
  ],
];
