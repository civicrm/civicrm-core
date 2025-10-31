<?php
use CRM_SearchKitReports_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_SearchKit_Reports',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'SearchKit_Reports',
        'label' => E::ts('SearchKit Reports'),
        'api_entity' => 'Afform',
        'api_params' => [
          'version' => 4,
          'select' => [
            'name',
            'title',
            'description',
            'placement:label',
            'server_route',
            'tags:label',
          ],
          'orderBy' => [],
          'where' => [
            ['server_route', 'IS NOT EMPTY'],
            ['placement:name', 'CONTAINS', 'reports'],
          ],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_SearchKit_Reports_SearchDisplay_SearchKit_Reports_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'SearchKit_Reports_Table',
        'label' => E::ts('SearchKit Reports Table'),
        'saved_search_id.name' => 'SearchKit_Reports',
        'type' => 'table',
        'settings' => [
          'description' => '',
          'sort' => [],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'title',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
              'cssRules' => [
                ['font-bold'],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'tags:label',
              'label' => E::ts('Tags'),
              'sortable' => TRUE,
            ],
            [
              'label' => E::ts('Actions'),
              'size' => 'btn-xs',
              'links' => [
                [
                  'path' => '[server_route]',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('Open'),
                  'style' => 'info',
                  'condition' => [],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                  'conditions' => [],
                ],
                [
                  'path' => 'civicrm/admin/afform#/edit/[name]',
                  'icon' => 'fa-pen-to-square',
                  'text' => E::ts('Edit'),
                  'style' => 'warning',
                  'condition' => [],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                  'conditions' => [],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-center',
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
          'actions_display_mode' => 'menu',
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
