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

use CRM_Recaptcha_ExtensionUtil as E;

/**
 * Settings metadata file
 */
return [
  'recaptchaPublicKey' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'recaptchaPublicKey',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_attributes' => [
      'size' => 64,
      'maxlength' => 64,
    ],
    'html_type' => 'text',
    'default' => NULL,
    'add' => '4.3',
    'title' => E::ts('reCAPTCHA Site Key'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
    'settings_pages' => [
      'recaptcha' => [
        'weight' => 10,
      ],
    ],
  ],
  'recaptchaPrivateKey' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'recaptchaPrivateKey',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_attributes' => [
      'size' => 64,
      'maxlength' => 64,
    ],
    'html_type' => 'text',
    'default' => NULL,
    'add' => '4.3',
    'title' => E::ts('reCAPTCHA Secret Key'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => NULL,
    'help_text' => NULL,
    'settings_pages' => [
      'recaptcha' => [
        'weight' => 10,
      ],
    ],
  ],
  'forceRecaptcha' => [
    'add' => '4.7',
    'help_text' => NULL,
    'is_domain' => 1,
    'is_contact' => 0,
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'forceRecaptcha',
    'type' => 'Boolean',
    'html_type' => 'toggle',
    'default' => '0',
    'title' => E::ts('Force reCAPTCHA on Contribution pages'),
    'description' => E::ts('If enabled, reCAPTCHA will show on all contribution pages.'),
    'settings_pages' => [
      'recaptcha' => [
        'weight' => 10,
      ],
    ],
  ],
];
