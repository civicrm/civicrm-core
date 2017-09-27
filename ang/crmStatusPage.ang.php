<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

// ODDITY: Angular name 'statuspage' doesn't match the file name 'crmStatusPage'.

return array(
  'ext' => 'civicrm',
  'js' => array('ang/crmStatusPage.js', 'ang/crmStatusPage/*.js'),
  'css' => array('ang/crmStatusPage.css'),
  'partials' => array('ang/crmStatusPage'),
  'settings' => array(),
  'requires' => array('crmUi', 'crmUtil', 'ngRoute', 'crmResource'),
);
