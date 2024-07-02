<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n
return [
  'js' => [
    'ang/oauthUtil.js',
    // 'ang/oauthUtil/*.js',
    // 'ang/oauthUtil/*/*.js',
  ],
  // 'css' => ['ang/oauthUtil.css'],
  // 'partials' => ['ang/oauthUtil'],
  // 'requires' => ['crmUi', 'crmUtil'],
  'settingsFactory' => ['CRM_OAuth_Angular', 'getSettings'],
  'exports' => [
    'oauth-util-import' => 'A',
    'oauth-util-grant-ctrl' => 'A',
  ],
];
