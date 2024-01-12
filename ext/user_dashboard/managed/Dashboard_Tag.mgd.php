<?php
use CRM_UserDashboard_ExtensionUtil as E;

return [
  [
    'name' => 'Dashboard_Tag',
    'entity' => 'Tag',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('User Dashboard'),
        'name' => 'UserDashboard',
        'description' => E::ts('Search will appear on the User Dashboard page'),
        'is_reserved' => TRUE,
        'used_for' => [
          'civicrm_saved_search',
        ],
        'color' => '#5d677b',
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
