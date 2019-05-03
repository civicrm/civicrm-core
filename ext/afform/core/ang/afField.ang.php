<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array (
  'js' => 
  array (
    0 => 'ang/afField.js',
    1 => 'ang/afField/*.js',
    2 => 'ang/afField/*/*.js',
  ),
  'css' => 
  array (
    0 => 'ang/afField.css',
  ),
  'partials' => 
  array (
    0 => 'ang/afField',
  ),
  'requires' => 
  array (
    0 => 'crmUi',
    1 => 'crmUtil',
    2 => 'ngRoute',
  ),
  'settings' => 
  array (
  ),
  'basePages' => [],
);
