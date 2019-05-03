<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'js' => [
    'ang/afField.js',
    'ang/afField/*.js',
    'ang/afField/*/*.js',
  ],
  'css' => ['ang/afField.css'],
  'partials' => ['ang/afField'],
  'requires' => [
    'crmUi',
    'crmUtil',
  ],
  'settings' => [],
  'basePages' => [],
];
