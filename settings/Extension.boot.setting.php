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
    'is_constant' => TRUE,
  ],
  'ext_repo_download' => [
    'group_name' => 'Extension Preferences',
    'group' => 'ext',
    'name' => 'ext_repo_download',
    'type' => 'Boolean',
    'html_type' => 'toggle',
    'default' => TRUE,
    'add' => '6.18',
    'title' => ts('Enable Extension Downloads'),
    'is_domain' => 1,
    'is_contact' => 0,
    'is_constant' => TRUE,
    'description' => ts('Allow downloading and installing/upgrading extensions through the web UI and API. Unlike "Extension Repo URL", this does not affect the ability to check for available extensions and updates -- it only controls whether they can be downloaded.'),
  ],
  'ext_max_depth' => [
    'bootstrap_comment' => 'This is a boot setting which may be loaded during bootstrap. Defaults are loaded via SettingsBag::getSystemDefaults().',
    'group_name' => 'Extension Preferences',
    'group' => 'ext',
    'name' => 'ext_max_depth',
    'type' => 'Integer',
    'quick_form_type' => 'Element',
    'html_type' => 'number',
    'html_attributes' => [
      'class' => 'six',
      'min' => 1,
    ],
    'default' => \CRM_Extension_System::DEFAULT_MAX_DEPTH,
    'add' => '5.55',
    'title' => ts('Extension Depth'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Maximum number of sub-directories to search when looking for extensions'),
    'settings_pages' => ['path' => ['weight' => 100]],
  ],
];
