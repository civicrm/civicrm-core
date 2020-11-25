<?php
// This file declares an Angular module which can be autoloaded
// ODDITY: Angular name 'statuspage' doesn't match the file name 'crmStatusPage'.

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmStatusPage.js', 'ang/crmStatusPage/*.js'],
  'css' => ['ang/crmStatusPage.css'],
  'partials' => ['ang/crmStatusPage'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'crmResource'],
];
