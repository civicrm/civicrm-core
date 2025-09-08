<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n
return [
  'js' => [
    'ang/crmMsgadm.js',
    'ang/crmMsgadm/*.js',
    'ang/crmMsgadm/*/*.js',
  ],
  'css' => [
    'ang/crmMsgadm.css',
  ],
  'partials' => [
    'ang/crmMsgadm',
  ],
  'bundles' => [
    'bootstrap3',
  ],
  'requires' => [
    'crmRouteBinder',
    'crmUi',
    'crmUtil',
    'crmDialog',
    'crmMonaco',
    'jsonFormatter',
    'ngRoute',
    'ngSanitize',
    'api4',
    'ui.bootstrap',
  ],
  'basePages' => [],
  'permissions' => [
    'edit message templates',
    'edit user-driven message templates',
    'edit system workflow message templates',
    'access CiviMail',
  ],
  'settingsFactory' => ['CRM_MessageAdmin_Settings', 'getAll'],
];
