<?php

// Angular module crmSearchEmailReport.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'ang/crmSearchDisplayEmailReport.js',
    'ang/crmSearchDisplayEmailReport/*.js',
    'ang/crmSearchDisplayEmailReport/*/*.js',
  ],
  'partials' => [
    'ang/crmSearchDisplayEmailReport',
  ],
  'requires' => ['crmSearchDisplay', 'crmUi', 'crmSearchTasks', 'ui.bootstrap', 'ui.sortable'],
  'bundles' => ['bootstrap3'],
  'exports' => [
    'crm-search-display-email-report' => 'E',
  ],
];
