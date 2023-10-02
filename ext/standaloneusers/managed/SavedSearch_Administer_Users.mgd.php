<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Users',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Users',
        'label' => E::ts('Administer Users'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'User',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'username',
            'uf_name',
            'is_active',
            'when_created',
            'when_last_accessed',
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
    'name' => 'SavedSearch_Users_SearchDisplay_Users',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Users',
        'label' => E::ts('Users'),
        'saved_search_id.name' => 'Users',
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
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('id'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'username',
              'dataType' => 'String',
              'label' => E::ts('Username'),
              'sortable' => TRUE,
              'link' => [
                'path' => '/civicrm/admin/user#?User1=[id]',
                'entity' => '',
                'action' => '',
                'join' => '',
                'target' => '',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'uf_name',
              'dataType' => 'String',
              'label' => E::ts('Email'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'dataType' => 'Boolean',
              'label' => E::ts('Active?'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'when_created',
              'dataType' => 'Timestamp',
              'label' => E::ts('When Created'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'when_last_accessed',
              'dataType' => 'Timestamp',
              'label' => E::ts('When Last Accessed'),
              'sortable' => TRUE,
            ],
          ],
          'actions' => [
            'delete',
            'disable',
            'download',
            'enable',
          ],
          'classes' => [
            'table',
            'table-striped',
          ],
          'addButton' => [
            'path' => '/civicrm/admin/user#',
            'text' => E::ts('Add User'),
            'icon' => 'fa-plus',
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
