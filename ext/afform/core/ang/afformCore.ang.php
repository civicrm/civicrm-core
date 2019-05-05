<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array(
  'js' => array(
    0 => 'ang/afformCore.js',
    1 => 'ang/afformCore/*.js',
    2 => 'ang/afformCore/*/*.js',
  ),
  'css' => array('ang/afformCore.css'),
  'requires' => array('crmUi', 'crmUtil', 'api4'),
  'partials' => array('ang/afformCore'),
  'settings' => array(),
  'basePages' => [],
);
