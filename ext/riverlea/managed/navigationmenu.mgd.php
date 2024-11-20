<?php

use CRM_riverlea_ExtensionUtil as E;

return [
    [
      'name' => 'riverlea_settings',
      'entity' => 'Navigation',
      'cleanup' => 'always',
      'update' => 'unmodified',
      'params' => [
        'version' => 4,
        'values' => [
          'label' => E::ts('Riverlea Settings'),
          'name' => 'riverlea_settings',
          'url' => 'civicrm/admin/setting/riverlea',
          'permission' => 'administer Riverlea',
          'permission_operator' => 'OR',
          'parent_id.name' => 'Customize Data and Screens',
          'is_active' => TRUE,
          'has_separator' => 0,
          'weight' => 90,
        ],
        'match' => ['name'],
      ],
    ],
];
