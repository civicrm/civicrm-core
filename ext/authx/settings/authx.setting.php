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
  $flows = ['param', 'header', 'xheader', 'login', 'auto'];
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
      'class' => 'crm-select2',
    ],
    'default' => ['site_key', 'perm'],
    'title' => ts('Authentication guard'),
    'help_text' => ts('Enable an authentication guard if you want to limit which users may authenticate via authx. The permission-based guard is satisfied by checking user permissions. The key-based guard is satisfied by checking the secret site-key. The JWT guard is satisfied if the user presents a signed token. If there are no guards, then any user can authenticate.'),
    'pseudoconstant' => [
      'callback' => ['\Civi\Authx\Meta', 'getGuardTypes'],
    ],
  ];
  foreach ($flows as $flow) {
    $s["authx_{$flow}_cred"] = $basic + [
      'name' => "authx_{$flow}_cred",
      'type' => 'Array',
      'quick_form_type' => 'Select',
      'html_type' => 'Select',
      'html_attributes' => [
        'multiple' => 1,
        'class' => 'crm-select2',
      ],
      'default' => ['jwt'],
      'title' => ts('Acceptable credentials (%1)', [1 => $flow]),
      'help_text' => NULL,
      'pseudoconstant' => [
        'callback' => ['\Civi\Authx\Meta', 'getCredentialTypes'],
      ],
    ];
    $s["authx_{$flow}_user"] = $basic + [
      'name' => "authx_{$flow}_user",
      'type' => 'String',
      'quick_form_type' => 'Select',
      'html_type' => 'Select',
      'html_attributes' => [
        'class' => 'crm-select2',
      ],
      'default' => 'optional',
      'title' => ts('User account requirements (%1)', [1 => $flow]),
      'help_text' => NULL,
      'pseudoconstant' => [
        'callback' => ['\Civi\Authx\Meta', 'getUserModes'],
      ],
    ];
  }

  $s['authx_param_cred']['default'] = ['jwt', 'api_key'];
  $s['authx_header_cred']['default'] = ['jwt', 'api_key'];
  $s['authx_xheader_cred']['default'] = ['jwt', 'api_key'];

  return $s;
};

/**
 * Settings metadata file
 */
return $_authx_settings();
