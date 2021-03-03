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
      'title' => ts('Acceptable credentials (%s)'),
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
      'title' => ts('User account requirements (%s)'),
      'help_text' => NULL,
      'pseudoconstant' => [
        'callback' => ['\Civi\Authx\Meta', 'getUserModes'],
      ],
    ];
  }
  return $s;
};

/**
 * Settings metadata file
 */
return $_authx_settings();
