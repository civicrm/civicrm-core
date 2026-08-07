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
 * Settings metadata for the SearchKit extension.
 */
return [
  'search_kit_timeout' => [
    'group_name' => 'Search Preferences',
    'group' => 'Search Preferences',
    'name' => 'search_kit_timeout',
    'type' => 'Integer',
    'html_type' => 'number',
    'html_attributes' => [
      'class' => 'six',
      'min' => 0,
    ],
    'default' => 0,
    'add' => '6.18',
    'title' => ts('SearchKit Query Timeout'),
    'is_domain' => 1,
    'is_contact' => 0,
    'help_text' => ts('Maximum number of seconds a SearchKit query may run before being cancelled. Set to 0 to disable. Requires MariaDB 10.2+ or MySQL 5.7+. Individual searches can override this value.'),
    'settings_pages' => ['search' => ['section' => 'searchkit', 'weight' => 10]],
  ],
];
