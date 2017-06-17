<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array(
  'ext' => 'civicrm',
  'js' => array('ang/crmCxn.js', 'ang/crmCxn/*.js'),
  'css' => array('ang/crmCxn.css'),
  'partials' => array('ang/crmCxn'),
  'requires' => array('crmUtil', 'ngRoute', 'ngSanitize', 'ui.utils', 'crmUi', 'dialogService', 'crmResource'),
);
