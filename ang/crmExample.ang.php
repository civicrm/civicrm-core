<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmExample.js'],
  'partials' => ['ang/crmExample'],
  'requires' => ['crmUtil', 'ngRoute', 'ui.utils', 'crmUi', 'crmResource'],
];
