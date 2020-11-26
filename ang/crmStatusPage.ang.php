<?php
// This file declares an Angular module which can be autoloaded

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmStatusPage.js', 'ang/crmStatusPage/*.js'],
  'css' => ['ang/crmStatusPage.css'],
  'partials' => ['ang/crmStatusPage'],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'crmResource'],
];
