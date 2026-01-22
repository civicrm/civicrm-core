<?php
use CRM_Grant_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_CiviGrant_Summary',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'CiviGrant_Summary',
        'label' => E::ts('CiviGrant Summary'),
        'form_values' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Grant',
        'api_params' => [
          'version' => 4,
          'select' => [
            'status_id:label',
            'grant_type_id:label',
            'amount_total',
            'amount_granted',
            'application_received_date',
            'grant_report_received',
            'money_transfer_date',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
        'mapping_id' => NULL,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_CiviGrant_Summary_SearchDisplay_Grant_Tab',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Grant_Tab',
        'label' => E::ts('Grant Tab'),
        'saved_search_id.name' => 'CiviGrant_Summary',
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'toolbar' => [
            [
              'entity' => 'Grant',
              'action' => 'add',
              'target' => 'crm-popup',
              'icon' => 'fa-plus',
              'text' => 'Add Grant',
              'style' => 'primary',
            ],
          ],
          'sort' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => 'Status',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'grant_type_id:label',
              'label' => 'Type',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'amount_total',
              'label' => 'Requested',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'amount_granted',
              'label' => 'Granted',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'application_received_date',
              'label' => 'Application received',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'grant_report_received',
              'label' => 'Report received',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'money_transfer_date',
              'label' => 'Money transferred',
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'Grant',
                  'action' => 'view',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => 'View',
                  'style' => 'default',
                ],
                [
                  'entity' => 'Grant',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => 'Edit',
                  'style' => 'default',
                ],
                [
                  'entity' => 'Grant',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => 'Delete',
                  'style' => 'danger',
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
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
