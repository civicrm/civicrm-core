<?php

return [
  [
    'name' => 'SavedSearch_User_driven_Messages',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'User_driven_Messages',
        'label' => 'User-driven Messages',
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'MessageTemplate',
        'api_params' => [
          'version' => 4,
          'select' => [
            'msg_title',
            'msg_subject',
            'is_active',
          ],
          'orderBy' => [],
          'where' => [
            [
              'workflow_name',
              'IS EMPTY',
            ],
          ],
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
    'name' => 'SavedSearch_User_driven_Messages_SearchDisplay_User_driven_Messages_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'User_driven_Messages_Table_1',
        'label' => 'User-driven Messages Table 1',
        'saved_search_id.name' => 'User_driven_Messages',
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [],
          'placeholder' => 5,
          'sort' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'msg_title',
              'dataType' => 'String',
              'label' => 'Message Template Title',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'msg_subject',
              'dataType' => 'Text',
              'label' => 'Message Template Subject',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'dataType' => 'Boolean',
              'label' => 'Enabled',
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'MessageTemplate',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => 'Edit',
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'MessageTemplate',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => 'Delete',
                  'style' => 'danger',
                  'path' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
          'addButton' => [
            'path' => 'civicrm/admin/messageTemplates/add?action=add&reset=1',
            'text' => 'Add Message Template',
            'icon' => 'fa-plus',
          ],
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];
