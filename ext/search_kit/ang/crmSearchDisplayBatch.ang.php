<?php
// Module for rendering Batch Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayBatch.module.js',
    'ang/crmSearchDisplayBatch/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayBatch',
  ],
  'css' => [],
  'basePages' => ['civicrm/search', 'civicrm/admin/search'],
  'requires' => ['crmSearchDisplay', 'crmUi', 'ui.bootstrap', 'crmSearchTasks'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-batch' => 'E',
  ],
];
