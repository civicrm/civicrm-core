<?php
// Module for rendering List Search Displays.
return [
  'js' => [
    'ang/crmSearchDisplayList.module.js',
    'ang/crmSearchDisplayList/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayList',
  ],
  'basePages' => [],
  'requires' => ['crmSearchDisplay', 'crmUi', 'ui.bootstrap', 'crmSearchTasks'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-list' => 'E',
  ],
];
