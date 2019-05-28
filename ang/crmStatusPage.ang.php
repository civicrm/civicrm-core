<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

// ODDITY: Angular name 'statuspage' doesn't match the file name 'crmStatusPage'.

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmStatusPage.js', 'ang/crmStatusPage/*.js'],
  'css' => ['ang/crmStatusPage.css'],
  'partials' => ['ang/crmStatusPage'],
  'settings' => [],
  'requires' => ['crmUi', 'crmUtil', 'ngRoute', 'crmResource'],
];
