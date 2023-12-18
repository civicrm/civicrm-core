<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Permissions',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Permissions',
        'label' => E::ts('Permissions'),
        'api_entity' => 'RolePermission',
        'api_params' => [
          'version' => 4,
          'select' => [
            'role_name',
            'role_label',
            'permission_group',
            'permission_name',
            'permission_title',
            'permission_description',
            'permission_granted',
          ],
          'orderBy' => [],
          'where' => [],
        ],
        'description' => E::ts('Shows the permissions of the different roles in standalone CiviCRM.'),
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
        'label' => E::ts('Permissions Table 1'),
        'saved_search_id.name' => 'Permissions',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('This lists all the permissions for each role. You need to click the the granted column to change it.
    It takes a while before the screen is shown.'),
          'sort' => [
            [
              'role_label',
              'ASC',
            ],
            [
              'permission_name',
              'ASC',
            ],
          ],
          'limit' => 0,
          'pager' => FALSE,
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'role_label',
              'dataType' => 2,
              'label' => E::ts('Role'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'permission_group',
              'dataType' => 2,
              'label' => E::ts('Component / Extension'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'html',
              'key' => 'permission_title',
              'dataType' => 2,
              'label' => E::ts('Permission'),
              'sortable' => TRUE,
              'rewrite' => '[permission_title]
    <p class="description">[permission_description]</p>',
              'title' => E::ts('[permission_description]'),
            ],
            [
              'type' => 'field',
              'key' => 'permission_granted',
              'dataType' => 16,
              'label' => E::ts('Granted'),
              'sortable' => TRUE,
              'rewrite' => '',
              'editable' => TRUE,
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
