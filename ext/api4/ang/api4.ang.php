<?php
// Autoloader data for Api4 angular module.
$vars = [
  'operators' => \CRM_Core_DAO::acceptedSQLOperators(),
];
\Civi::resources()->addVars('api4', $vars);
return [
  'js' => [
    'ang/api4.js',
    'ang/api4/*.js',
    'ang/api4/*/*.js',
  ],
  'css' => [
    'css/explorer.css',
  ],
  'partials' => [
    'ang/api4',
  ],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'crmRouteBinder'],
];
