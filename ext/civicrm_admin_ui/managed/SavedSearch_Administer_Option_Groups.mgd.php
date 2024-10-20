<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Administer_Option_Groups',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Option_Groups',
        'label' => E::ts('Administer Option Groups'),
        'form_values' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'OptionGroup',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'title',
            'description',
            'is_active',
            'is_reserved',
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
    'name' => 'SavedSearch_Administer_Option_Groups_SearchDisplay_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Option_Groups_Display',
        'label' => E::ts('Option Groups'),
        'saved_search_id.name' => 'Administer_Option_Groups',
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
              'key' => 'title',
              'dataType' => 'String',
              'label' => E::ts('Title'),
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
                  'text' => E::ts('Settings'),
                  'style' => 'default',
                  'condition' => [],
                  'entity' => 'OptionGroup',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'entity' => 'OptionGroup',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-list',
                  'text' => E::ts('Edit Options'),
                  'style' => 'primary',
                  'path' => 'civicrm/admin/option/[name]',
                  'condition' => ['is_locked', '=', FALSE],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
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
