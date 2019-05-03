<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array (
  'js' => 
  array (
    0 => 'ang/af.js',
    1 => 'ang/af/*.js',
    2 => 'ang/af/*/*.js',
  ),
  'css' => 
  array (
    0 => 'ang/af.css',
  ),
  'partials' => 
  array (
    0 => 'ang/af',
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
