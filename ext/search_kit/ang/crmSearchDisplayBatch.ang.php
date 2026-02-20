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
  'basePages' => [],
  'requires' => ['crmSearchDisplay', 'crmUi', 'ui.bootstrap', 'crmSearchTasks'],
  'bundles' => ['bootstrap3'],
  'permissions' => ['administer queues'],
  'exports' => [
    'crm-search-display-batch' => 'E',
  ],
];
