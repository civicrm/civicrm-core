<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'js' => [
    'ang/afCore.js',
    'ang/afCore/*.js',
    'ang/afCore/*/*.js',
  ],
  'css' => ['ang/afCore.css'],
  'requires' => ['crmUi', 'crmUtil', 'api4', 'checklist-model'],
  'partials' => ['ang/afCore'],
  'settings' => [],
  'basePages' => [],
];
