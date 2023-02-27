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
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'ContactType',
        'api_params' => [
          'version' => 4,
          'select' => [
            'label',
            'parent_id:label',
            'description',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
      ],
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
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'placeholder' => 5,
          'sort' => [
            [
              'parent_id:label',
              'ASC',
            ],
            [
              'label',
              'ASC',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'label',
              'dataType' => 'String',
              'label' => E::ts('Label'),
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
              'key' => 'parent_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Parent'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'icon' => 'fa-lock',
                  'side' => 'left',
                  'if' => [
                    'parent_id:label',
                    'IS EMPTY',
                  ],
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'dataType' => 'Text',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'ContactType',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'ContactType',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'path' => '',
                  'condition' => [
                    'parent_id:label',
                    'IS NOT EMPTY',
                  ],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
          'addButton' => [
            'path' => 'civicrm/admin/options/subtype/edit?action=add&reset=1',
            'text' => E::ts('Add Contact Type'),
            'icon' => 'fa-plus',
          ],
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];
