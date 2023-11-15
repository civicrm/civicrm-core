<?php

use CRM_Legacycustomsearches_ExtensionUtil as E;

return [
  [
    'name' => 'Custom Searches',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Custom Searches'),
        'name' => 'Custom Searches',
        'url' => 'civicrm/contact/search/custom/list?reset=1',
        'permission' => NULL,
        'permission_operator' => 'OR',
        'parent_id.name' => 'Search',
        'is_active' => TRUE,
        'has_separator' => 2,
        'weight' => 15,
      ],
      'match' => ['domain_id', 'name'],
    ],
  ],
  [
    'name' => 'Manage Custom Searches',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('Manage Custom Searches'),
        'name' => 'Manage Custom Searches',
        'url' => 'civicrm/admin/options/custom_search?reset=1',
        'permission' => 'administer CiviCRM',
        'permission_operator' => 'OR',
        'parent_id.name' => 'Customize Data and Screens',
        'is_active' => TRUE,
        'weight' => 15,
      ],
      'match' => ['domain_id', 'name'],
    ],
  ],
];
