<?php
use CRM_riverlea_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Riverlea_Streams',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Riverlea_Streams',
        'label' => E::ts('Riverlea Streams'),
        'api_entity' => 'RiverleaStream',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'label',
            'description',
            'is_active',
            'name',
            'is_reserved',
            'has_base',
            'css_file',
            'css_file_dark',
            'vars',
            'vars_dark',
            'custom_css',
            'custom_css_dark',
            'file_prefix',
            'extension',
          ],
          'orderBy' => [],
          'where' => [
            ['is_active', '=', TRUE],
          ],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_Riverlea_Streams_SearchDisplay_Riverlea_Streams',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Riverlea_Streams',
        'label' => E::ts('Riverlea Streams'),
        'saved_search_id.name' => 'Riverlea_Streams',
        'type' => 'list',
        'settings' => [
          'classes' => [
            'crm-riverlea-streams-list',
          ],
          'style' => 'ul',
          'limit' => 50,
          'sort' => [
            ['is_reserved', 'DESC'],
            ['label', 'ASC'],
          ],
          'pager' => [
            'show_count' => FALSE,
            'expose_limit' => FALSE,
            'hide_single' => TRUE,
          ],
          'columns' => [
            [
              'path' => '~/riverleaStreamList/riverleaStreamListItem.html',
              'type' => 'include',
            ],
          ],
          'placeholder' => 5,
          'symbol' => 'none',
          'toolbar' => [
            [
              'entity' => 'RiverleaStream',
              'text' => E::ts('Create new Stream'),
              'icon' => 'fa-plus',
              'target' => 'crm-popup',
              'action' => 'add',
              'style' => 'primary',
              'join' => '',
              'path' => '',
              'task' => '',
              'condition' => [],
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
