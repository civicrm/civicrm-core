<?php
// Autoloader data for SearchDisplay module.
return [
  'js' => [
    'ang/crmSearchPage.module.js',
    'ang/crmSearchPage/*.js',
    'ang/crmSearchPage/*/*.js',
  ],
  'partials' => [
    'ang/crmSearchPage',
  ],
  'basePages' => ['civicrm/search'],
  'requires' => ['ngRoute', 'api4', 'crmUi'],
  'settingsFactory' => ['\Civi\Search\Display', 'getPageSettings'],
];
