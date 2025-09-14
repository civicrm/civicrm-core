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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
/*
 * Settings metadata file
 */
return [
  'userFrameworkBaseURL' => [
    'group_name' => 'URL Preferences',
    'group' => 'url',
    'name' => 'userFrameworkBaseURL',
    'type' => 'String',
    'default' => NULL,
    'title' => ts('User Framework Base URL'),
    'description' => ts('The base URL of the user framework or CMS that CiviCRM is running in.'),
    'help_text' => NULL,
    'is_domain' => 1,
    'is_contact' => 0,
    'is_constant' => TRUE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_UF_BASEURL',
    'add' => '5.80',
  ],
  'userFrameworkResourceURL' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'name' => 'userFrameworkResourceURL',
    'title' => ts('CiviCRM Resource URL'),
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '[civicrm.root]/',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Absolute URL of the location where the civicrm module or component has been installed.'),
    'help_text' => NULL,
    'validate_callback' => 'CRM_Utils_Rule::urlish',
    'settings_pages' => ['url' => ['weight' => 0]],
  ],
  'imageUploadURL' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'title' => ts('Image Upload URL'),
    'name' => 'imageUploadURL',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '[civicrm.files]/persist/contribute/',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('URL of the location for uploaded image files.'),
    'help_text' => NULL,
    'validate_callback' => 'CRM_Utils_Rule::urlish',
    'settings_pages' => ['url' => ['weight' => 10]],
  ],
  'customCSSURL' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'name' => 'customCSSURL',
    'title' => ts('Custom CSS URL'),
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => NULL,
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('You can modify the look and feel of CiviCRM by adding your own stylesheet. For small to medium sized modifications, use your css file to override some of the styles in civicrm.css. Or if you need to make drastic changes, you can choose to disable civicrm.css completely.'),
    'help_text' => NULL,
    'validate_callback' => 'CRM_Utils_Rule::urlish',
    'settings_pages' => ['url' => ['weight' => 20]],
  ],
  'extensionsURL' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group' => 'url',
    'group_name' => 'URL Preferences',
    'title' => ts('Extension Resource URL'),
    'name' => 'extensionsURL',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '[civicrm.files]/ext/',
    'add' => '4.1',
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Base URL for extension resources (images, stylesheets, etc). This should correspond to the extensionsDir path.'),
    'help_text' => NULL,
    'validate_callback' => 'CRM_Utils_Rule::urlish',
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_SETTING_EXTENSIONS_URL',
    'settings_pages' => ['url' => ['weight' => 40]],
  ],
];
