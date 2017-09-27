<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return array(
  'ext' => 'civicrm',
  'js' => array('ang/crmUi.js'),
  'partials' => array('ang/crmUi'),
  'requires' => array('crmResource'),
);
