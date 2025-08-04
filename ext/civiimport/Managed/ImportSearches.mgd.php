<?php

use Civi\BAO\Import;
use CRM_Civiimport_ExtensionUtil as E;

$managedEntities = [];
$importEntities = Import::getImportTables();
foreach ($importEntities as $importEntity) {
  try {
    $fields = array_merge(['_id' => TRUE, '_status' => TRUE, '_entity_id' => TRUE, '_status_message' => TRUE], Import::getFieldsForUserJobID($importEntity['user_job_id'], FALSE));
  }
  catch (CRM_Core_Exception $e) {
    continue;
  }
  $fields['_entity_id']['link'] = [
    'entity' => $fields['_entity_id']['fk_entity'],
    'action' => 'view',
    'target' => '_blank',
    'join' => '_entity_id',
  ];
  $createdBy = empty($importEntity['created_by']) ? '' : ' (' . E::ts('Created by %1', [$importEntity['created_by'], 'String']) . ')';
  $managedEntities[] = [
    'name' => 'SavedSearch_Import' . $importEntity['user_job_id'],
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Import' . '_' . $importEntity['user_job_id'],
        'label' => $importEntity['title'] . ' ' . $importEntity['description'],
        'api_entity' => 'Import' . '_' . $importEntity['user_job_id'],
        'api_params' => [
          'version' => 4,
          'select' => array_keys($fields),
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => $importEntity['expires_date'],
        'created_date' => $importEntity['created_date'],
        'created_id' => $importEntity['created_id'],
        'description' => ts('Temporary import data'),
        'mapping_id' => NULL,
      ],
      'match' => [
        'name',
      ],
    ],
  ];
  $managedEntities[] = [
    'name' => 'SavedSearch_Import_Summary' . $importEntity['user_job_id'],
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Import_Summary' . '_' . $importEntity['user_job_id'],
        'label' => E::ts('Import Summary') . ' ' . $importEntity['description'],
        'api_entity' => 'Import' . '_' . $importEntity['user_job_id'],
        'api_params' => [
          'version' => 4,
          'select' => ['_status', 'COUNT(_id) AS COUNT__id'],
          'orderBy' => [],
          'where' => [],
          'groupBy' => ['_status'],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => $importEntity['expires_date'],
        'created_date' => $importEntity['created_date'],
        'created_id' => $importEntity['created_id'],
        'description' => ts('Temporary import data'),
        'mapping_id' => NULL,
      ],
      'match' => [
        'name',
      ],
    ],
  ];
  $columns = [];
  foreach ($fields as $field) {
    $columns[] = [
      'type' => 'field',
      'key' => $field['name'],
      'dataType' => $field['data_type'] ?? 'String',
      'label' => $field['title'] ?? $field['label'],
      'sortable' => TRUE,
      'editable' => !str_starts_with($field['name'], '_'),
      'link' => $field['link'] ?? NULL,
    ];
  }
  $managedEntities[] = [
    'name' => 'SavedSearchDisplay_Import' . $importEntity['user_job_id'],
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Import' . '_' . $importEntity['user_job_id'],
        'label' => E::ts('Import') . ' ' . $importEntity['user_job_id'] . $createdBy,
        'saved_search_id.name' => 'Import' . '_' . $importEntity['user_job_id'],
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'limit' => 25,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'sort' => [],
          'columns' => $columns,
        ],
        'acl_bypass' => FALSE,
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ];

  $managedEntities[] = [
    'name' => 'SavedSearchDisplay_Import_Summary' . $importEntity['user_job_id'],
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Import_Summary' . '_' . $importEntity['user_job_id'],
        'label' => E::ts('Import Summary') . ' ' . $importEntity['user_job_id'] . $createdBy,
        'saved_search_id.name' => 'Import_Summary' . '_' . $importEntity['user_job_id'],
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 40,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'sort' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => '_status',
              'dataType' => 'String',
              'label' => 'Row status',
              'sortable' => TRUE,
              'link' => [
                'path' => 'civicrm/search#/display/Import_' . $importEntity['user_job_id'] . '/Import_' . $importEntity['user_job_id'] . '?_status=[_status]',
                'entity' => '',
                'action' => '',
                'join' => '',
                'target' => '',
              ],
              'rewrite' => '[_status]',
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_id',
              'dataType' => 'Integer',
              'label' => E::ts('Number of rows'),
              'sortable' => TRUE,
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
  ];
}
return $managedEntities;
