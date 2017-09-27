<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// http://wiki.civicrm.org/confluence/display/CRMDOC/hook_civicrm_angularModules

// ODDITY: Only loads if you have CiviMail permissions.
// ODDITY: Extra resources loaded via CRM_Mailing_Info::getAngularModules.

return array(
  'ext' => 'civicrm',
  'js' => array(
    'ang/crmMailing.js',
    'ang/crmMailing/*.js',
  ),
  'css' => array('ang/crmMailing.css'),
  'partials' => array('ang/crmMailing'),
  'requires' => array('crmUtil', 'crmAttachment', 'crmAutosave', 'ngRoute', 'ui.utils', 'crmUi', 'dialogService', 'crmResource'),
);
