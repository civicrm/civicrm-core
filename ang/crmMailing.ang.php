<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

// ODDITY: Only loads if you have CiviMail permissions.
// ODDITY: Extra resources loaded via CRM_Mailing_Info::getAngularModules.

return [
  'ext' => 'civicrm',
  'js' => [
    'ang/crmMailing.js',
    'ang/crmMailing/*.js',
  ],
  'css' => ['ang/crmMailing.css'],
  'partials' => ['ang/crmMailing'],
  'settingsFactory' => ['CRM_Mailing_Info', 'createAngularSettings'],
  'requires' => ['crmUtil', 'crmAttachment', 'crmAutosave', 'ngRoute', 'ui.utils', 'crmUi', 'dialogService', 'crmResource'],
  'permissions' => [
    'view all contacts',
    'edit all contacts',
    'access CiviMail',
    'create mailings',
    'schedule mailings',
    'approve mailings',
    'delete in CiviMail',
    'edit message templates',
  ],
];
