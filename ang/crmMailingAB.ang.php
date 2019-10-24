<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

// ODDITY: Only loads if you have CiviMail permissions.
// ODDITY: Extra resources loaded via CRM_Mailing_Info::getAngularModules.

return [
  'ext' => 'civicrm',
  'js' => [
    'ang/crmMailingAB.js',
    'ang/crmMailingAB/*.js',
    'ang/crmMailingAB/*/*.js',
  ],
  'css' => ['ang/crmMailingAB.css'],
  'partials' => ['ang/crmMailingAB'],
  'requires' => ['ngRoute', 'ui.utils', 'crmUi', 'crmAttachment', 'crmMailing', 'crmD3', 'crmResource'],
];
