<?php
// Autoloader data for SearchDisplay module.
return [
  'js' => [
    'ang/crmSearchPage.module.js',
    'ang/crmSearchPage/*.js',
    'ang/crmSearchPage/*/*.js',
  ],
  'basePages' => ['civicrm/search'],
  'requires' => ['ngRoute', 'api4', 'crmUi'],
  'partialsCallback' => ['\Civi\Search\Display', 'getPartials'],
];
