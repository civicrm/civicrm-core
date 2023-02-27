<?php

use CRM_Civiimport_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_My_imports',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'My_imports',
        'label' => E::ts('My imports'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'UserJob',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'created_date',
            'status_id:label',
            'job_type:label',
          ],
          'orderBy' => [],
          'where' => [
            [
              'created_id',
              '=',
              'user_contact_id',
            ],
            [
              'is_template',
              '=',
              FALSE,
            ],
          ],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
      ],
    ],
  ],
];
