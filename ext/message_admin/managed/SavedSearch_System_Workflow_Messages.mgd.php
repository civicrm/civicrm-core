<?php

return [
  [
    'name' => 'SavedSearch_System_Workflow_Messages',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'System_Workflow_Messages',
        'label' => E::ts('System Workflow Messages'),
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
    'cleanup' => 'always',
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
              'label' => E::ts('Workflow'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'master_id',
              'dataType' => 'Integer',
              'label' => E::ts('Changed from default?'),
              'sortable' => TRUE,
              'rewrite' => "{if '[master_id]' == ''}No{else}Yes{/if}",
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => 'civicrm/admin/messageTemplates/#/edit?id=[id]',
                  'condition' => [],
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
