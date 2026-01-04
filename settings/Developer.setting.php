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
  'assetCache' => [
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'assetCache',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      //'class' => 'crm-select2',
    ],
    'default' => 'auto',
    'add' => '4.7',
    'title' => ts('Asset Caching'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Store computed JS/CSS content in cache files? (Note: In "Auto" mode, the "Debug" setting will determine whether to activate the cache.)'),
    'pseudoconstant' => [
      'callback' => '\Civi\Core\AssetBuilder::getCacheModes',
    ],
    'settings_pages' => ['debug' => ['weight' => 600]],
  ],
  // note: this setting is only exposed if the userFramework declares it has support
  // (currently only Drupal)
  // @see CRM_Utils_System_Base::supportsUfLogging
  'userFrameworkLogging' => [
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'userFrameworkLogging',
    'type' => 'Boolean',
    'html_type' => 'toggle',
    'default' => '0',
    'add' => '4.3',
    'title' => ts('Send CiviCRM errors to CMS logs'),
    'is_domain' => 1,
    'is_contact' => 0,
    'metadata_callback' => ['CRM_Core_BAO_Setting', 'userFrameworkLoggingMetadataCallback'],
    'help_text' => [
      ts('Set this value to Yes if you want CiviCRM error/debugging messages to appear in your CMS error log.'),
      ts('In the case of Drupal, this will cause all CiviCRM error messages to appear in the Watchdog logs (assuming you have Watchdog enabled)'),
    ],
    'settings_pages' => ['debug' => ['weight' => 100]],
  ],
  'debug_enabled' => [
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'debug_enabled',
    // we can't call the setting debug as that has other meanings in api
    'config_key' => 'debug',
    'type' => 'Boolean',
    'html_type' => 'toggle',
    // default to TRUE for demo or localhost sites.
    'default' => str_contains(CIVICRM_UF_BASEURL, 'localhost') || str_contains(CIVICRM_UF_BASEURL, 'demo.civicrm.org'),
    'add' => '4.3',
    'title' => ts('Enable Debugging'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => [
      ts("Set this value to Yes if you want to use one of CiviCRM's debugging tools. This feature should NOT be enabled for production sites."),
      ts('Debug output is triggered by adding specific name-value pairs to the CiviCRM query string.'),
    ],
    'help_doc_url' => [
      'page' => 'dev/tools/debugging/#using-url-parameters',
    ],
    'settings_pages' => ['debug' => ['weight' => 200]],
  ],
  'backtrace' => [
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'backtrace',
    'type' => 'Boolean',
    'html_type' => 'toggle',
    'default' => '0',
    'add' => '4.3',
    'title' => ts('Display Backtrace'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Set this value to Yes if you want to display a backtrace listing when a fatal error is encountered. This feature should NOT be enabled for production sites.'),
    'help_doc_url' => [
      'page' => 'dev/tools/debugging/#displaying-a-backtrace',
    ],
    'settings_pages' => ['debug' => ['weight' => 300]],
  ],
  'environment' => [
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'environment',
    'type' => 'String',
    'html_type' => 'Select',
    'quick_form_type' => 'Select',
    'default' => 'Production',
    'pseudoconstant' => [
      'optionGroupName' => 'environment',
    ],
    'add' => '4.7',
    'title' => ts('Environment'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Setting to define the environment in which this CiviCRM instance is running.'),
    'on_change' => [
      'CRM_Core_BAO_Setting::onChangeEnvironmentSetting',
    ],
    'settings_pages' => ['debug' => ['weight' => 400]],
  ],
  'esm_loader' => [
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'esm_loader',
    'type' => 'String',
    'quick_form_type' => 'Select',
    'html_type' => 'Select',
    'html_attributes' => [
      //'class' => 'crm-select2',
    ],
    'default' => 'auto',
    'add' => '5.63',
    'title' => ts('ECMAScript Module Loader'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Specify how to load ESM (JS) files. The "Default" mode is the supported option. Other options may assist with diagnosing or temporarily mitigating compatibility issues.'),
    'options' => ['auto' => ts('Default (Auto-detect)'), 'browser' => ts('Browser'), 'shim-fast' => ts('es-module-shims (fast mode)'), 'shim-slow' => ts('es-module-shims (slow mode)')],
    'settings_pages' => ['debug' => ['weight' => 700]],
  ],
  'fatalErrorHandler' => [
    'group_name' => 'Developer Preferences',
    'group' => 'developer',
    'name' => 'fatalErrorHandler',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'default' => NULL,
    'add' => '4.3',
    'title' => ts('Fatal Error Handler'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Enter the path and class for a custom PHP error-handling function if you want to override built-in CiviCRM error handling for your site.'),
    'settings_pages' => ['debug' => ['weight' => 500]],
  ],
];
