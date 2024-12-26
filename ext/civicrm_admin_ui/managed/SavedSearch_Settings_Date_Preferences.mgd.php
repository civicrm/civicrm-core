<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Settings_Date_Preferences',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Settings_Date_Preferences',
        'label' => E::ts('Settings - Date Preferences'),
        'api_entity' => 'PreferencesDate',
        'api_params' => [
          'version' => 4,
          'select' => [
            'name',
            'description',
            'date_format',
            'start',
            'end',
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
    'name' => 'SavedSearch_Settings_Date_Preferences_SearchDisplay_Settings_Date_Preferences',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Settings_Date_Preferences',
        'label' => E::ts('Settings - Date Preferences'),
        'saved_search_id.name' => 'Settings_Date_Preferences',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
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
              'key' => 'name',
              'dataType' => 'String',
              'label' => E::ts('Date Class'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'dataType' => 'String',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'date_format',
              'dataType' => 'String',
              'label' => E::ts('Date Format'),
              'sortable' => TRUE,
              'rewrite' => '',
              'empty_value' => 'Default',
            ],
            [
              'type' => 'field',
              'key' => 'start',
              'dataType' => 'Integer',
              'label' => E::ts('Start Offset'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'end',
              'dataType' => 'Integer',
              'label' => E::ts('End Offset'),
              'sortable' => TRUE,
            ],
            [
              'links' => [
                [
                  'entity' => 'PreferencesDate',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => '',
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'links',
              'alignment' => 'text-right',
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
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
