<?php
// Dashboard Angular module configuration
return [
  'ext' => 'civicrm',
  'js' => [
    'ang/crmDashboard.js',
    'ang/crmDashboard/*.js',
    'ang/crmDashboard/*/*.js',
  ],
  'css' => ['css/dashboard.css'],
  'partials' => ['ang/crmDashboard'],
  'partialsCallback' => ['CRM_Core_BAO_Dashboard', 'angularPartials'],
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'ui.sortable', 'dialogService', 'api4'],
  'settingsFactory' => ['CRM_Core_BAO_Dashboard', 'angularSettings'],
  'permissions' => ['administer CiviCRM'],
  'bundles' => ['bootstrap3', 'visual'],
];
