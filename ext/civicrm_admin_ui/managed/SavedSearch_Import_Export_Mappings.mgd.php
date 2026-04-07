<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Import_Export_Mappings',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Import_Export_Mappings',
        'label' => E::ts('Import/Export Mappings'),
        'api_entity' => 'Mapping',
        'api_params' => [
          'version' => 4,
          'select' => [
            'name',
            'description',
            'mapping_type_id:label',
            'id',
          ],
          'orderBy' => [],
          'where' => [
            [
              'mapping_type_id:name',
              '!=',
              'Search Builder',
            ],
          ],
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
    'name' => 'SavedSearch_Import_Export_Mappings_SearchDisplay_Import_Export_Mappings',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Import_Export_Mappings',
        'label' => E::ts('Import/Export Mappings'),
        'saved_search_id.name' => 'Import_Export_Mappings',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'mapping_type_id:label',
              'ASC',
            ],
            [
              'name',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => E::ts('ID'),
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
              'key' => 'mapping_type_id:label',
              'label' => E::ts('Mapping Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'path' => '',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'condition' => [],
                  'task' => '',
                  'entity' => 'Mapping',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'task' => 'delete',
                  'entity' => 'Mapping',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'path' => '',
                  'action' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => [
            'delete',
            'update',
          ],
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
        ],
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
