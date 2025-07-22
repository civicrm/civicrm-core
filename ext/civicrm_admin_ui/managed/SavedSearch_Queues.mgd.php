<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Queues',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Queues',
        'label' => E::ts('Queues'),
        'api_entity' => 'Queue',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'type:label',
            'status:label',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Queues_SearchDisplay_Queues_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Queues_Table_1',
        'label' => E::ts('Queues'),
        'saved_search_id.name' => 'Queues',
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
              'label' => E::ts('System Queue ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'name',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'type:label',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status:label',
              'label' => E::ts('Status'),
              'sortable' => TRUE,
              'rewrite' => '',
              'editable' => TRUE,
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
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
