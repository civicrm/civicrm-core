<?php
// Search Admin module - for composing & saving searches & displays.
return [
  'js' => [
    'ang/crmSearchAdmin.module.js',
    'ang/crmSearchAdmin/*.js',
    'ang/crmSearchAdmin/*/*.js',
  ],
  'css' => [
    'css/crmSearchAdmin.css',
  ],
  'partials' => [
    'ang/crmSearchAdmin',
  ],
  'bundles' => ['bootstrap3'],
  'basePages' => ['civicrm/admin/search'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'ui.sortable', 'ui.bootstrap', 'api4', 'crmSearchTasks', 'crmRouteBinder'],
  'settingsFactory' => ['\Civi\Search\Admin', 'getAdminSettings'],
];
