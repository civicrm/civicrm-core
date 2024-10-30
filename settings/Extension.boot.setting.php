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
  'ext_repo_url' => [
    'group_name' => 'Extension Preferences',
    'group' => 'ext',
    'name' => 'ext_repo_url',
    'type' => 'String',
    'quick_form_type' => 'Element',
    'html_attributes' => [
      'size' => 64,
      'maxlength' => 128,
    ],
    'html_type' => 'text',
    'default' => 'https://civicrm.org/extdir/ver={ver}',
    'add' => '4.3',
    'title' => ts('Extension Repo URL'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => '',
  ],
  'ext_max_depth' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Extension Preferences',
    'group' => 'ext',
    'name' => 'ext_max_depth',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'text',
    'html_attributes' => [
      'size' => 4,
      'maxlength' => 8,
    ],
    'default' => \CRM_Extension_System::DEFAULT_MAX_DEPTH,
    'add' => '5.55',
    'title' => ts('Extension Depth'),
    'is_domain' => 1,
    'is_contact' => 0,
    'description' => ts('Maximum number of sub-directories to search when looking for extensions'),
    'help_text' => NULL,
    'settings_pages' => ['path' => ['weight' => 100]],
  ],
];
