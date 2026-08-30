<?php

return [
  [
    'name' => 'Navigation_Manage_Tags',
    'entity' => 'Navigation',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Manage_Tags',
        'label' => ts('Manage Tags (SearchKit)'),
        'url' => 'civicrm/admin/tags',
        'permission' => ['administer CiviCRM', 'manage tags'],
        'permission_operator' => 'OR',
        'parent_id.name' => 'Customize Data and Screens',
        'weight' => 26,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
];
