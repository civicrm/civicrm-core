<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

// ODDITY: This only loads if CiviCase is active.

CRM_Core_Resources::singleton()->addSetting([
  'crmCaseType' => [
    'REL_TYPE_CNAME' => CRM_Case_XMLProcessor::REL_TYPE_CNAME,
  ],
]);

return [
  'ext' => 'civicrm',
  'js' => ['ang/crmCaseType.js'],
  'css' => ['ang/crmCaseType.css'],
  'partials' => ['ang/crmCaseType'],
  'requires' => ['ngRoute', 'ui.utils', 'crmUi', 'unsavedChanges', 'crmUtil', 'crmResource'],
];
