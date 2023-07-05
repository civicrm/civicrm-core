<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Roles',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Roles',
        'label' => E::ts('Roles'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Role',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'label',
            'is_active',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Roles_SearchDisplay_Roles_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Roles_Table_1',
        'label' => E::ts('Roles Table 1'),
        'saved_search_id.name' => 'Roles',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'label',
              'dataType' => 'String',
              'label' => E::ts('Label'),
              'sortable' => TRUE,
              'link' => [
                'path' => '/civicrm/admin/role#?Role1=[id]',
                'entity' => '',
                'action' => '',
                'join' => '',
                'target' => '',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'dataType' => 'Boolean',
              'label' => E::ts('Active'),
              'sortable' => TRUE,
              'rewrite' => '',
              'alignment' => '',
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
          ],
        ],
        'acl_bypass' => FALSE,
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
