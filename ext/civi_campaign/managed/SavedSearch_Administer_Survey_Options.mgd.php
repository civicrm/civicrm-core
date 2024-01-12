<?php
use CRM_Campaign_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Administer_Survey_Options',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Survey_Options',
        'label' => E::ts('Administer Survey Options'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'OptionValue',
        'api_params' => [
          'version' => 4,
          'select' => [
            'label',
            'value',
            'filter',
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
    'name' => 'SavedSearch_Administer_Survey_Options_SearchDisplay_Administer_Survey_Options_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Survey_Options_Table',
        'label' => E::ts('Administer Survey Options'),
        'saved_search_id.name' => 'Administer_Survey_Options',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
          ],
          'draggable' => 'weight',
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'label',
              'dataType' => 'String',
              'label' => E::ts('Label'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'value',
              'dataType' => 'String',
              'label' => E::ts('Value'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'filter',
              'dataType' => 'Integer',
              'label' => E::ts('Recontact Interval'),
              'sortable' => TRUE,
              'editable' => TRUE,
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
