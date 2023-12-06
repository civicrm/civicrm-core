<?php
use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_label_format',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'label_format',
        'label' => E::ts('label format'),
        'api_entity' => 'OptionGroup',
        'api_params' => [
          'version' => 4,
          'select' => [
            'OptionGroup_OptionValue_option_group_id_01.label',
            'title',
            'description',
            'is_reserved',
            'OptionGroup_OptionValue_option_group_id_01.grouping',
            'OptionGroup_OptionValue_option_group_id_01.is_default',
          ],
          'orderBy' => [],
          'where' => [
            [
              'name',
              'IN',
              [
                'label_format',
                'name_badge',
              ],
            ],
          ],
          'groupBy' => [],
          'join' => [
            [
              'OptionValue AS OptionGroup_OptionValue_option_group_id_01',
              'LEFT',
              [
                'id',
                '=',
                'OptionGroup_OptionValue_option_group_id_01.option_group_id',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_label_format_SearchDisplay_label_format_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'label_format_Table_1',
        'label' => E::ts('label format Table 1'),
        'saved_search_id.name' => 'label_format',
        'type' => 'table',
        'settings' => [
          'description' => '',
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'show_count' => FALSE,
            'expose_limit' => FALSE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'OptionGroup_OptionValue_option_group_id_01.label',
              'dataType' => 'String',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'title',
              'dataType' => 'String',
              'label' => E::ts('Used for'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'OptionGroup_OptionValue_option_group_id_01.grouping',
              'dataType' => 'String',
              'label' => E::ts('Grouping'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'OptionGroup_OptionValue_option_group_id_01.is_default',
              'dataType' => 'Boolean',
              'label' => E::ts('Default'),
              'sortable' => TRUE,
              'rewrite' => '{ }',
              'icons' => [
                [
                  'icon' => 'fa-check',
                  'side' => 'left',
                  'if' => [
                    'OptionGroup_OptionValue_option_group_id_01.is_default',
                    '=',
                    TRUE,
                  ],
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'is_reserved',
              'dataType' => 'Boolean',
              'label' => E::ts('Reserved'),
              'sortable' => TRUE,
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
                  'path' => 'civicrm/admin/labelFormats/edit?action=update&id=[OptionGroup_OptionValue_option_group_id_01.id]&group=[name]&reset=1',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'path' => 'civicrm/admin/labelFormats/edit?action=copy&id=[OptionGroup_OptionValue_option_group_id_01.id]&group=[name]&reset=1',
                  'icon' => 'fa-clone',
                  'text' => E::ts('Copy'),
                  'style' => 'default',
                  'condition' => [],
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
          ],
          'toolbar' => [
            [
              'action' => '',
              'entity' => '',
              'text' => E::ts('Add Label Format'),
              'icon' => '',
              'style' => 'default',
              'target' => 'crm-popup',
              'join' => '',
              'path' => 'civicrm/admin/labelFormats/edit?action=add&reset=1',
              'task' => '',
              'condition' => [],
            ],
          ],
          'headerCount' => FALSE,
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
