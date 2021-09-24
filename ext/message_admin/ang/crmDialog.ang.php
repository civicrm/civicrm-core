<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n
return [
  'js' => [
    'ang/crmDialog.js',
  ],
  'requires' => [
    //'crmUi',
    //'crmUtil',
    'dialogService',
  ],
  'settings' => [],
  'basePages' => [],
];
