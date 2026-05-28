<?php

// Angular module searchAdminDisplayEmailReport.
// @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
return [
  'js' => [
    'ang/searchAdminDisplayEmailReport.js',
    'ang/searchAdminDisplayEmailReport/*.js',
    'ang/searchAdminDisplayEmailReport/*/*.js',
  ],
  'partials' => [
    'ang/searchAdminDisplayEmailReport',
  ],
  'requires' => [
    'crmSearchAdmin',
    'crmSearchDisplayEmailReport',
  ],
  'basePages' => ['civicrm/admin/search'],
  'settingsFactory' => ['\Civi\Search\Admin', 'getEmailReportAdminSettings'],
];
