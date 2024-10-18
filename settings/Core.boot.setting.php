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
    'quick_form_type' => 'YesNo',
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
    'description' => NULL,
    'help_text' => NULL,
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
];
