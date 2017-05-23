<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

// ODDITY: This only loads if CiviCase is active.

CRM_Core_Resources::singleton()->addSetting(array(
  'crmCaseType' => array(
    'REL_TYPE_CNAME' => CRM_Case_XMLProcessor::REL_TYPE_CNAME,
  ),
));

return array(
  'ext' => 'civicrm',
  'js' => array('ang/crmCaseType.js'),
  'css' => array('ang/crmCaseType.css'),
  'partials' => array('ang/crmCaseType'),
  'requires' => array('ngRoute', 'ui.utils', 'crmUi', 'unsavedChanges', 'crmUtil', 'crmResource'),
);
