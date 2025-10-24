<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

use CRM_Authx_ExtensionUtil as E;

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
$_authx_settings = function() {
  $weight = 10;
  $flows = [
    'auto' => ts('Auto Login'),
    'header' => ts('HTTP Header'),
    'login' => ts('HTTP Session Login'),
    'param' => ts('HTTP Parameter'),
    'xheader' => ts('HTTP X-Header'),
    'legacyrest' => ts('Legacy REST'),
    'pipe' => ts('Pipe'),
    'script' => ts('Script'),
  ];
  $basic = [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'authx',
    'is_domain' => 1,
    'is_contact' => 0,
    'add' => '5.36',
  ];

  $s = [];
  $s["authx_guards"] = $basic + [
    'name' => 'authx_guards',
    'type' => 'Array',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      'multiple' => 1,
      'class' => 'huge crm-select2',
    ],
    'default' => ['site_key', 'perm'],
    'title' => ts('Authentication guard'),
    'help_text' => ts('Enable an authentication guard if you want to limit which users may authenticate via authx. The permission-based guard is satisfied by checking user permissions. The key-based guard is satisfied by checking the secret site-key. If there are no guards, then any user can authenticate.'),
    'pseudoconstant' => [
      'callback' => ['\Civi\Authx\Meta', 'getGuardTypes'],
    ],
    'settings_pages' => ['authx' => ['weight' => $weight]],
  ];
  foreach ($flows as $flow => $flowLabel) {
    $weight = $weight + 10;
    $s["authx_{$flow}_cred"] = $basic + [
      'name' => "authx_{$flow}_cred",
      'type' => 'Array',
      'quick_form_type' => 'Select',
      'html_type' => 'Select',
      'html_attributes' => [
        'multiple' => 1,
        'class' => 'huge crm-select2',
      ],
      'default' => ['jwt'],
      'title' => ts('Acceptable credentials (%1)', [1 => $flowLabel]),
      'help_text' => ts('Specify which types of credentials are allowed in this authentication flow.'),
      'help_doc_url' => [
        'page' => 'dev/framework/authx/',
      ],
      'pseudoconstant' => [
        'callback' => ['\Civi\Authx\Meta', 'getCredentialTypes'],
      ],
      'settings_pages' => ['authx' => ['weight' => 1000 + $weight]],
    ];
    $s["authx_{$flow}_user"] = $basic + [
      'name' => "authx_{$flow}_user",
      'type' => 'String',
      'quick_form_type' => 'Select',
      'html_type' => 'Select',
      'html_attributes' => [
        'class' => 'huge crm-select2',
      ],
      'default' => 'optional',
      'title' => ts('User account requirements (%1)', [1 => $flowLabel]),
      'help_markup' => ts('CiviCRM Contacts are often attached to CMS User Accounts. When authenticating a Contact, should it also load the User Account?'),
      'help_doc_url' => [
        'page' => 'dev/framework/authx/',
      ],
      'pseudoconstant' => [
        'callback' => ['\Civi\Authx\Meta', 'getUserModes'],
      ],
      'settings_pages' => ['authx' => ['weight' => 2000 + $weight]],
    ];
  }

  // Override defaults for a few specific elements
  $s['authx_legacyrest_cred']['default'] = ['jwt', 'api_key'];
  $s['authx_legacyrest_user']['default'] = 'require';
  $s['authx_param_cred']['default'] = ['jwt', 'api_key'];
  $s['authx_header_cred']['default'] = []; /* @see \authx_civicrm_install() */
  $s['authx_xheader_cred']['default'] = ['jwt', 'api_key'];
  $s['authx_pipe_cred']['default'] = ['jwt', 'api_key'];

  return $s;
};

/**
 * Settings metadata file
 */
return $_authx_settings();
