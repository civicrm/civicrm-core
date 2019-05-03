<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'js' => [
    'ang/afBlock.js',
    'ang/afBlock/*.js',
    'ang/afBlock/*/*.js',
  ],
  'css' => ['ang/afBlock.css'],
  'partials' => ['ang/afBlock'],
  'requires' => [
    'crmUi',
    'crmUtil',
  ],
  'settings' => [],
  'basePages' => [],
];
