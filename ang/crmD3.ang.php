<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

// ODDITY: Only loads if you have CiviMail permissions.
// ODDITY: Extra resources loaded via CRM_Mailing_Info::getAngularModules.

return array(
  'ext' => 'civicrm',
  'js' => array(
    'ang/crmD3.js',
    'bower_components/d3/d3.min.js',
  ),
  'requires' => array(),
);
