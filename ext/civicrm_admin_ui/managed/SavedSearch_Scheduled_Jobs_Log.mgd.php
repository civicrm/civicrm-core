<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Scheduled_Jobs_Log',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Scheduled_Jobs_Log',
        'label' => E::ts('Scheduled Jobs Log'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'JobLog',
        'api_params' => [
          'version' => 4,
          'select' => [
            'run_time',
            'name',
            'command',
            'description',
            'data',
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
    'name' => 'SavedSearch_Scheduled_Jobs_Log_SearchDisplay_Scheduled_Jobs_Log_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Scheduled_Jobs_Log_Table_1',
        'label' => E::ts('Scheduled Jobs Log Table 1'),
        'saved_search_id.name' => 'Scheduled_Jobs_Log',
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'description' => NULL,
          'sort' => [
            [
              'run_time',
              'DESC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'run_time',
              'label' => E::ts('Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'html',
              'key' => 'name',
              'label' => E::ts('Job Name and Command'),
              'sortable' => TRUE,
              'rewrite' => '[name]<br><br>[command]',
            ],
            [
              'type' => 'html',
              'key' => 'description',
              'label' => E::ts('Output'),
              'sortable' => TRUE,
              'rewrite' => '<b>' . E::ts("Summary:") . '</b> [description]<br><b>' . E::ts("Details:") . '</b><pre>[data]</pre>',
            ],
          ],
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
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
