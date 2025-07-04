<?php
use CRM_Campaign_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Administer_Campaigns',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Campaigns',
        'label' => E::ts('Administer Campaigns'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Campaign',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'title',
            'description',
            'is_active',
            'start_date',
            'end_date',
            'campaign_type_id:label',
            'status_id:label',
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
    'name' => 'SavedSearch_Administer_Campaigns_SearchDisplay_Campaigns_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Campaigns_Table',
        'label' => E::ts('Administer Campaigns'),
        'saved_search_id.name' => 'Administer_Campaigns',
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => FALSE,
            'expose_limit' => TRUE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'sort' => [
            ['is_active', 'DESC'],
            ['title', 'ASC'],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'title',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'start_date',
              'label' => E::ts('Start Date'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'end_date',
              'label' => E::ts('End Date'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'campaign_type_id:label',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => E::ts('Status'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'label' => E::ts('Enabled'),
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'path' => '',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'condition' => [],
                  'entity' => 'Campaign',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'task' => 'enable',
                  'entity' => 'Campaign',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-on',
                  'text' => E::ts('Enable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'task' => 'disable',
                  'entity' => 'Campaign',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-off',
                  'text' => E::ts('Disable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'entity' => 'Campaign',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger small-popup',
                  'path' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'Campaign',
              'action' => 'add',
              'target' => 'crm-popup',
              'icon' => 'fa-plus',
              'text' => E::ts('Add Campaign'),
              'style' => 'primary',
            ],
          ],
          'cssRules' => [
            [
              'disabled',
              'is_active',
              '=',
              FALSE,
            ],
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
