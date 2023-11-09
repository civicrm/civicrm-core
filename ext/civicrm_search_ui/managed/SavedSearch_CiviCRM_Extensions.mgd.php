<?php

use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_CiviCRM_Extensions',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'CiviCRM_Extensions',
        'label' => E::ts('CiviCRM Extensions'),
        'api_entity' => 'Extension',
        'api_params' => [
          'version' => 4,
          'select' => [
            'label',
            'key',
            'description',
            'status:label',
            'tags',
            'version',
          ],
          'orderBy' => [],
          'where' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_CiviCRM_Extensions_SearchDisplay_CiviCRM_Extensions_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'CiviCRM_Extensions_Table_1',
        'label' => E::ts('CiviCRM Extensions Table 1'),
        'saved_search_id.name' => 'CiviCRM_Extensions',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'label',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'html',
              'key' => 'label',
              'dataType' => 'String',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
              'cssRules' => [],
              'rewrite' => '<b>[label]</b><br>[description]<br><i>[key]</i>',
            ],
            [
              'type' => 'field',
              'key' => 'status:label',
              'dataType' => 'String',
              'label' => E::ts('Status'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'tags',
              'dataType' => 'Array',
              'label' => E::ts('Tags'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'version',
              'dataType' => 'String',
              'label' => E::ts('Version'),
              'sortable' => TRUE,
              'icons' => [],
            ],
          ],
          'actions' => TRUE,
          'classes' => [
            'table',
          ],
          'cssRules' => [
            [
              'bg-success',
              'status:name',
              '=',
              'installed',
            ],
            [
              'bg-danger',
              'status:name',
              '=',
              'installed-missing',
            ],
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
