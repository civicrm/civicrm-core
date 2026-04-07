<?php

use CRM_riverlea_ExtensionUtil as E;

return [
    [
      'name' => 'Navigation_ThemeSettings',
      'entity' => 'Navigation',
      'cleanup' => 'always',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'label' => E::ts('Theme Settings'),
          'name' => 'ThemeSettings',
          'url' => 'civicrm/admin/theme',
          'permission' => 'administer CiviCRM',
          'parent_id.name' => 'Customize Data and Screens',
          'is_active' => TRUE,
          'has_separator' => 0,
          'weight' => 90,
        ],
        'match' => ['name'],
      ],
    ],
];
