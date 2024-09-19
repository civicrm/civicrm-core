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
  'partialsCallback' => ['CRM_Contact_Page_DashBoard', 'angularPartials'],
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'ui.sortable', 'dialogService', 'api4'],
  'settingsFactory' => ['CRM_Contact_Page_DashBoard', 'angularSettings'],
  'permissions' => ['administer CiviCRM'],
  'bundles' => ['visual'],
];
