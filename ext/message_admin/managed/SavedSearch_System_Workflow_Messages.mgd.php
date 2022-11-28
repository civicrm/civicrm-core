<?php

return [
  [
    'name' => 'SavedSearch_System_Workflow_Messages',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'System_Workflow_Messages',
        'label' => 'System Workflow Messages',
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'MessageTemplate',
        'api_params' => [
          'version' => 4,
          'select' => [
            'msg_title',
            'id',
            'master_id',
          ],
          'orderBy' => [],
          'where' => [
            [
              'workflow_name',
              'IS NOT EMPTY',
            ],
            [
              'is_default',
              '=',
              TRUE,
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
    'name' => 'SavedSearch_System_Workflow_Messages_SearchDisplay_System_Workflow_Messages_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'System_Workflow_Messages_Table_1',
        'label' => 'System Workflow Messages Table 1',
        'saved_search_id.name' => 'System_Workflow_Messages',
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
          'sort' => [
            [
              'msg_title',
              'ASC',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'msg_title',
              'dataType' => 'String',
              'label' => 'Workflow',
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'MessageTemplate',
                  'action' => 'update',
                  'join' => '',
                  'target' => '',
                  'icon' => 'fa-pencil',
                  'text' => 'Edit',
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
                [
                  'path' => 'civicrm/admin/messageTemplates?action=view&id=[master_id]&reset=1',
                  'icon' => 'fa-external-link',
                  'text' => 'View Default',
                  'style' => 'default',
                  'condition' => [
                    'master_id',
                    'IS NOT EMPTY',
                  ],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
                [
                  'path' => 'civicrm/admin/messageTemplates&action=revert&id=[id]&selectedChild=workflow',
                  'icon' => 'fa-external-link',
                  'text' => 'Revert to Default',
                  'style' => 'danger',
                  'condition' => [
                    'master_id',
                    'IS NOT EMPTY',
                  ],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ],
    ],
  ],
];
