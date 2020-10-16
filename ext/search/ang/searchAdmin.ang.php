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
  'basePages' => [],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'ui.sortable', 'ui.bootstrap', 'dialogService', 'api4', 'searchActions'],
  'settingsFactory' => ['\Civi\Search\Admin', 'getAdminSettings'],
];
