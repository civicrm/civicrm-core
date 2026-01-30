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

/**
 * Settings metadata file
 */
return [
  'installed' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'installed',
    'type' => 'Boolean',
    'html_type' => 'toggle',
    'default' => FALSE,
    'add' => '4.7',
    'title' => ts('System Installed'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('A flag indicating whether this system has run a post-installation routine'),
    'help_text' => NULL,
  ],
  'enable_components' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'enable_components',
    'type' => 'Array',
    'html_type' => 'checkboxes',
    // The default list of components should be kept in sync with "civicrm_extension.sqldata.php".
    'default' => ['CiviEvent', 'CiviContribute', 'CiviMember', 'CiviMail', 'CiviReport', 'CiviPledge'],
    'add' => '4.4',
    'title' => ts('Enable Components'),
    'is_domain' => 0,
    'is_contact' => 0,
    'validate_callback' => 'CRM_Core_Component::validateComponents',
    'on_change' => [
      'CRM_Case_Info::onToggleComponents',
      'CRM_Core_Component::preToggleComponents',
    ],
    'post_change' => [
      'CRM_Core_Component::postToggleComponents',
    ],
    'pseudoconstant' => [
      'callback' => 'CRM_Core_SelectValues::getComponentSelectValues',
    ],
    'settings_pages' => ['component' => ['weight' => 0]],
  ],
  'domain' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'domain',
    'type' => 'String',
    'default' => 1,
    'title' => ts('CiviCRM Domain ID'),
    'description' => ts('The current domain if CiviCRM is running multi-site.'),
    'help_text' => NULL,
    'is_domain' => 1,
    'is_contact' => 0,
    'is_constant' => TRUE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_DOMAIN_ID',
    'add' => '5.80',
  ],
  // NOTE: consumers should probably not check the value of this setting directly
  // instead please use CRM_Utils_System::isMaintenanceMode()
  'core_maintenance_mode' => [
    'group_name' => 'CiviCRM Preferences',
    'group' => 'core',
    'name' => 'core_maintenance_mode',
    'type' => 'String',
    'default' => 'inherit',
    'options' => [
      '0' => 'Off',
      '1' => 'On',
      'inherit' => 'Inherit from CMS',
    ],
    'title' => ts('CiviCRM Maintenance Mode'),
    'description' => ts('Enabling Maintenance Mode will restrict certain functionality such as scheduled job runs and REST api calls. If not set, CiviCRM will attempt to check whether the CMS is in maintenance mode.'),
    'help_text' => NULL,
    'is_domain' => 1,
    'is_contact' => 0,
    'is_constant' => FALSE,
    'is_env_loadable' => TRUE,
    'global_name' => 'CIVICRM_MAINTENANCE_MODE',
    'add' => '6.0',
  ],
];
