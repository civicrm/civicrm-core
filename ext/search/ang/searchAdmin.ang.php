<?php
// Autoloader data for search builder.
return [
  'js' => [
    'ang/searchAdmin.module.js',
    'ang/searchAdmin/*.js',
    'ang/searchAdmin/*/*.js',
  ],
  'css' => [
    'css/*.css',
  ],
  'partials' => [
    'ang/searchAdmin',
  ],
  'basePages' => ['civicrm/admin/search'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'ui.sortable', 'ui.bootstrap', 'api4', 'crmSearchDisplay', 'crmSearchActions'],
  'settingsFactory' => ['\Civi\Search\Admin', 'getAdminSettings'],
];
