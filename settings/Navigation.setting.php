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
 * Settings related to navigation menu
 */
return [
  'menubar_position' => [
    'group' => 'navigation',
    'group_name' => 'Navigation settings',
    'name' => 'menubar_position',
    'type' => 'String',
    'html_type' => 'select',
    'default' => 'over-cms-menu',
    'add' => '5.12',
    'title' => ts('Menubar position'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Location of the CiviCRM main menu.'),
    'options' => [
      'over-cms-menu' => ts('Replace website menu'),
      'below-cms-menu' => ts('Below website menu'),
      'above-crm-container' => ts('Above content area'),
      'none' => ts('None - disable menu'),
    ],
    'settings_pages' => ['display' => ['section' => 'theme', 'weight' => 100]],
  ],
  'menubar_color' => [
    'group' => 'navigation',
    'group_name' => 'Navigation settings',
    'name' => 'menubar_color',
    'type' => 'String',
    'html_type' => 'color',
    'default' => '#1b1b1b',
    'add' => '5.13',
    'title' => ts('Menubar color'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Color of the CiviCRM main menu.'),
    'validate_callback' => 'CRM_Utils_Color::normalize',
    'settings_pages' => ['display' => ['section' => 'theme', 'weight' => 110]],
  ],
];
