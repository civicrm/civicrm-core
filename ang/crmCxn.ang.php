<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmCxn.js', 'ang/crmCxn/*.js'],
  'css' => ['ang/crmCxn.css'],
  'partials' => ['ang/crmCxn'],
  'requires' => ['crmUtil', 'ngRoute', 'ngSanitize', 'ui.utils', 'crmUi', 'dialogService', 'crmResource'],
];
