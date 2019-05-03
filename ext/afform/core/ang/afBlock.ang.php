<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array (
  'js' => 
  array (
    0 => 'ang/afBlock.js',
    1 => 'ang/afBlock/*.js',
    2 => 'ang/afBlock/*/*.js',
  ),
  'css' => 
  array (
    0 => 'ang/afBlock.css',
  ),
  'partials' => 
  array (
    0 => 'ang/afBlock',
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
