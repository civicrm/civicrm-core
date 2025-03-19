<?php
use CRM_Standaloneusers_ExtensionUtil as E;

$items = [
  [
    'name' => 'SavedSearch_Permissions',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Permissions',
        'label' => E::ts('Administer Role Permissions'),
        'api_entity' => 'RolePermission',
        'api_params' => [
          'version' => 4,
          'select' => [
            'name',
            'title',
          ],
          'orderBy' => [],
          'where' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Permissions_SearchDisplay_Permissions_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Permissions_Table_1',
        'label' => E::ts('Administer Role Permissions'),
        'saved_search_id.name' => 'Permissions',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'title',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [
            'expose_limit' => TRUE,
          ],
          'placeholder' => 10,
          'columns' => [
            [
              'type' => 'html',
              'key' => 'title',
              'label' => E::ts('Permission'),
              'sortable' => TRUE,
              'rewrite' => '[title]<p class="description">[description]</p>',
            ],
          ],
          'actions' => [
            'update',
          ],
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
          'hierarchical' => TRUE,
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];

$roles = \Civi\Api4\Role::get(FALSE)
  ->addSelect('name', 'label')
  ->addWhere('name', '!=', 'admin')
  ->execute()
  ->column('label', 'name');

foreach ($roles as $roleName => $roleLabel) {
  $items[0]['params']['values']['api_params']['select'][] = 'granted_' . $roleName;
  $items[1]['params']['values']['settings']['columns'][] = [
    'type' => 'field',
    'key' => 'granted_' . $roleName,
    'label' => $roleLabel,
    'sortable' => FALSE,
    'rewrite' => ' ',
    'icons' => [
      [
        'icon' => 'fa-square-check',
        'side' => 'left',
        'if' => [
          'granted_' . $roleName,
          '=',
          TRUE,
        ],
      ],
      [
        'icon' => 'fa-circle-check',
        'side' => 'left',
        'if' => [
          'implied_' . $roleName,
          '=',
          TRUE,
        ],
      ],
    ],
    'cssRules' => [
      [
        'bg-success',
        'granted_' . $roleName,
        '=',
        TRUE,
      ],
      [
        'disabled',
        'implied_' . $roleName,
        '=',
        TRUE,
      ],
    ],
    'alignment' => 'text-center',
  ];
}

return $items;
