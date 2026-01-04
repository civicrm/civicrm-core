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
        'label' => $importEntity['title'] . ' ' . $importEntity['description'],
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
}
return $managedEntities;
