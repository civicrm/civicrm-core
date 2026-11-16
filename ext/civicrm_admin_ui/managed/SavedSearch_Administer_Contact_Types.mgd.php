<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Administer_Contact_Types',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Contact_Types',
        'label' => E::ts('Administer Contact Types'),
        'api_entity' => 'ContactType',
        'api_params' => [
          'version' => 4,
          'select' => ['label', 'description', 'parent_id', 'is_active'],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_Administer_Contact_Types_SearchDisplay_Contact_Types_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Types_Table',
        'label' => E::ts('Contact Types Table'),
        'saved_search_id.name' => 'Administer_Contact_Types',
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'sort' => [
            ['label', 'ASC'],
          ],
          'cssRules' => [
            ['disabled', 'is_active', '=', FALSE],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'label',
              'label' => 'Label',
              'sortable' => TRUE,
              'icons' => [
                [
                  'field' => 'icon',
                  'side' => 'left',
                ],
              ],
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'label' => 'Description',
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => 'ContactType',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => 'Edit',
                  'style' => 'default',
                  'path' => '',
                ],
                [
                  'style' => 'default',
                  'text' => 'Add Subtype',
                  'icon' => 'fa-plus',
                  'conditions' => [
                    ['parent_id', 'IS EMPTY'],
                  ],
                  'path' => 'civicrm/admin/options/subtype/edit?action=add&reset=1&parent_id=[id]',
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'task' => 'enable',
                  'entity' => 'ContactType',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-on',
                  'text' => 'Enable',
                  'style' => 'default',
                ],
                [
                  'task' => 'disable',
                  'entity' => 'ContactType',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-off',
                  'text' => 'Disable',
                  'style' => 'default',
                  'conditions' => [
                    ['parent_id', 'IS NOT EMPTY'],
                  ],
                ],
                [
                  'entity' => 'ContactType',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => 'Delete',
                  'style' => 'danger',
                  'path' => '',
                  'conditions' => [
                    ['parent_id', 'IS NOT EMPTY'],
                  ],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'ContactType',
              'action' => 'add',
              'target' => 'crm-popup',
              'style' => 'primary',
              'text' => 'Add Contact Type',
              'icon' => 'fa-plus',
            ],
          ],
          'hierarchical' => TRUE,
          'columnMode' => 'custom',
          'actions_display_mode' => 'menu',
        ],
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
