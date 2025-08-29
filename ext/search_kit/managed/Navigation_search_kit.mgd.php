<?php
use CRM_Search_ExtensionUtil as E;

return [
  [
    'name' => 'Navigation_search_kit',
    'entity' => 'Navigation',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'label' => E::ts('SearchKit'),
        'name' => 'search_kit',
        'url' => 'civicrm/admin/search',
        'icon' => 'crm-i fa-search-plus',
        'permission' => [
          'manage own search_kit',
        ],
        'permission_operator' => 'OR',
        'parent_id.name' => 'Search',
        'is_active' => TRUE,
        'has_separator' => 2,
        'weight' => 13,
      ],
      'match' => ['domain_id', 'name'],
    ],
  ],
];
