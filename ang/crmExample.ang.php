<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array(
  'ext' => 'civicrm',
  'js' => array('ang/crmExample.js'),
  'partials' => array('ang/crmExample'),
  'requires' => array('crmUtil', 'ngRoute', 'ui.utils', 'crmUi', 'crmResource'),
);
