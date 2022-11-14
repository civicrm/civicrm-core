<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Administer_Custom_Groups',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Custom_Groups',
        'label' => E::ts('Administer Custom Groups'),
        'form_values' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'CustomGroup',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'title',
            'is_active',
            'extends:label',
            'extends_entity_column_id:label',
            'style:label',
            'COUNT(CustomGroup_CustomField_custom_group_id_01.id) AS COUNT_CustomGroup_CustomField_custom_group_id_01_id',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'CustomField AS CustomGroup_CustomField_custom_group_id_01',
              'LEFT',
              [
                'id',
                '=',
                'CustomGroup_CustomField_custom_group_id_01.custom_group_id',
              ],
            ],
          ],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
        'mapping_id' => NULL,
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Administer_Custom_Groups_SearchDisplay_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Table',
        'label' => E::ts('Table'),
        'saved_search_id.name' => 'Administer_Custom_Groups',
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
              'key' => 'id',
              'dataType' => 'String',
              'label' => E::ts('ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'title',
              'dataType' => 'String',
              'label' => E::ts('Group Title'),
              'sortable' => TRUE,
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
              'type' => 'field',
              'key' => 'extends_entity_column_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Used for'),
              'sortable' => TRUE,
              'empty_value' => '[extends:label]',
            ],
            [
              'type' => 'field',
              'key' => 'style:label',
              'dataType' => 'String',
              'label' => E::ts('Style'),
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                  'icon' => 'fa-list-alt',
                  'text' => E::ts('Fields (%1)', [1 => '[COUNT_CustomGroup_CustomField_custom_group_id_01_id]']),
                  'style' => 'default',
                  'path' => 'civicrm/admin/custom/group/fields#/?gid=[id]',
                ],
                [
                  'entity' => 'CustomGroup',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Settings'),
                  'style' => 'default',
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
            [
              'text' => '',
              'style' => 'default-outline',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => 'CustomGroup',
                  'action' => 'preview',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-eye',
                  'text' => E::ts('Preview Group'),
                  'style' => 'default',
                ],
                [
                  'entity' => 'CustomGroup',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete Group'),
                  'style' => 'danger',
                ],
              ],
              'type' => 'menu',
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
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];
