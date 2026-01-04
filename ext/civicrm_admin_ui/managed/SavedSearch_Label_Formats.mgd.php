<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Label_Formats',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Label_Formats',
        'label' => E::ts('Label Formats'),
        'api_entity' => 'OptionValue',
        'api_params' => [
          'version' => 4,
          'select' => [
            'label',
            'option_group_id:label',
            'grouping',
            'is_default',
            'is_reserved',
          ],
          'orderBy' => [],
          'where' => [
            [
              'option_group_id:name',
              'IN',
              [
                'label_format',
                'name_badge',
              ],
            ],
          ],
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
    'name' => 'SavedSearch_Label_Formats_SearchDisplay_Label_Formats_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Label_Formats_Table_1',
        'label' => E::ts('Label Formats Table 1'),
        'saved_search_id.name' => 'Label_Formats',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'label',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'grouping',
              'label' => E::ts('Grouping'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_reserved',
              'label' => E::ts('Reserved'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_default',
              'label' => E::ts('Default'),
              'sortable' => TRUE,
              'rewrite' => '[none]',
              'icons' => [
                [
                  'icon' => 'fa-check',
                  'side' => 'left',
                  'if' => ['is_default', '=', TRUE],
                ],
              ],
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => 'civicrm/admin/labelFormats/edit?action=update&id=[id]&group=[option_group_id:name]&reset=1',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-clone',
                  'text' => E::ts('Copy'),
                  'style' => 'default',
                  'path' => 'civicrm/admin/labelFormats/edit?action=copy&id=[id]&group=[option_group_id:name]&reset=1',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'path' => 'civicrm/admin/labelFormats/edit?action=delete&id=[id]&group=[option_group_id:name]&reset=1',
                  'icon' => 'fa-trash-o',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'condition' => [
                    'is_reserved',
                    '=',
                    FALSE,
                  ],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
          'draggable' => 'weight',
          'toolbar' => [
            [
              'path' => 'civicrm/admin/labelFormats/edit?action=add&group=[option_group_id:name]&reset=1',
              'icon' => 'fa-plus',
              'text' => E::ts('Add'),
              'style' => 'default',
              'condition' => [],
              'task' => '',
              'entity' => '',
              'action' => '',
              'join' => '',
              'target' => 'crm-popup',
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
