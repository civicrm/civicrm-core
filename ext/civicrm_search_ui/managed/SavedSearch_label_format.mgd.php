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
            'is_active',
            'is_reserved',
            'OptionGroup_OptionValue_option_group_id_01.grouping',
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
          'pager' => [],
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
              'key' => 'is_active',
              'dataType' => 'Boolean',
              'label' => E::ts('Enabled'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_reserved',
              'dataType' => 'Boolean',
              'label' => E::ts('Reserved'),
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => '',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => 'civicrm/admin/labelFormats?action=update&id=[OptionGroup_OptionValue_option_group_id_01.id]&group=[name]&reset=1',
                  'task' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                  'icon' => '',
                  'text' => E::ts('Copy'),
                  'style' => 'default',
                  'path' => 'civicrm/admin/labelFormats?action=copy&id=[OptionGroup_OptionValue_option_group_id_01.id]&group=[name]&reset=1',
                  'task' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'OptionValue',
                  'action' => 'delete',
                  'join' => 'OptionGroup_OptionValue_option_group_id_01',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'path' => '',
                  'task' => '',
                  'condition' => [
                    'OptionGroup_OptionValue_option_group_id_01.grouping',
                    '=',
                    'Custom',
                  ],
                ],
              ],
              'type' => 'buttons',
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
              'path' => 'civicrm/admin/labelFormats?action=add&reset=1',
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
