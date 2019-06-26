<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'js' => [
    'ang/afformCore.js',
    'ang/afformCore/*.js',
    'ang/afformCore/*/*.js',
  ],
  'css' => ['ang/afformCore.css'],
  'requires' => ['crmUi', 'crmUtil', 'api4', 'checklist-model'],
  'partials' => ['ang/afformCore'],
  'settings' => [],
  'basePages' => [],
];
