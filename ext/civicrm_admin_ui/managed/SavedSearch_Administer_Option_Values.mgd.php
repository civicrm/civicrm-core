<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Administer_Option_Values',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Option_Values',
        'label' => E::ts('Administer Option Values'),
        'form_values' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'OptionValue',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'label',
            'value',
            'description',
            'is_active',
            'is_reserved',
            'is_default',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
        'mapping_id' => NULL,
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Administer_Option_Values_SearchDisplay_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Option_Values_Display',
        'label' => E::ts('Option Values'),
        'saved_search_id.name' => 'Administer_Option_Values',
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'sort' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'dataType' => 'String',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'label',
              'dataType' => 'String',
              'label' => E::ts('Label'),
              'sortable' => TRUE,
              'editable' => TRUE,
              'icons' => [
                [
                  'field' => 'icon',
                  'side' => 'left',
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'value',
              'dataType' => 'String',
              'label' => E::ts('Value'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'html',
              'key' => 'description',
              'dataType' => 'String',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_default',
              'dataType' => 'Boolean',
              'label' => E::ts('Default'),
              'sortable' => TRUE,
              'editable' => TRUE,
              'icons' => [
                [
                  'icon' => 'fa-check',
                  'side' => 'left',
                  'if' => [
                    'is_default',
                    'IS NOT EMPTY',
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
              'icons' => [
                [
                  'icon' => 'fa-lock',
                  'side' => 'left',
                  'if' => [
                    'is_reserved',
                    'IS NOT EMPTY',
                  ],
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'dataType' => 'Boolean',
              'label' => E::ts('Enabled'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'path' => '',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'condition' => [],
                  'entity' => 'OptionValue',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'entity' => 'OptionValue',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'path' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
          'draggable' => 'weight',
          'cssRules' => [
            [
              'disabled',
              'is_active',
              '=',
              FALSE,
            ],
          ],
          'addButton' => [
            'path' => 'civicrm/admin/options?reset=1&action=add&gid=[option_group_id]',
            'text' => E::ts('Add Option'),
            'icon' => 'fa-plus',
          ],
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];
