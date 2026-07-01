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
  'permissions' => ['administer CiviCRM'],
  'bundles' => ['bootstrap3', 'visual'],
];
