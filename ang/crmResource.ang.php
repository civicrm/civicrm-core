<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

return [
  'ext' => 'civicrm',
  // 'js' => array('js/angular-crmResource/byModule.js'), // One HTTP request per module.
  // One HTTP request for all modules.
  'js' => ['js/angular-crmResource/all.js'],
];
