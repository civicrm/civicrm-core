<?php
use CRM_MessageAdmin_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Message_Templates_User',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Message_Templates_User',
        'label' => E::ts('User-Driven Messages'),
        'api_entity' => 'MessageTemplate',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'msg_title',
            'msg_subject',
            'is_active',
          ],
          'orderBy' => [],
          'where' => [
            ['workflow_name', 'IS EMPTY'],
            ['is_reserved', '=', FALSE],
          ],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_Message_Templates_User_SearchDisplay_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Message_Templates_User_Table',
        'label' => E::ts('User-Driven Messages'),
        'saved_search_id.name' => 'Message_Templates_User',
        'type' => 'table',
        'settings' => [
          'limit' => 50,
          'sort' => [['msg_title', 'ASC']],
          'pager' => ['show_count' => TRUE, 'expose_limit' => TRUE],
          'placeholder' => 5,
          // `revert` restores a template from the packaged original it was copied from, which
          // a user-driven template does not have, so it would report reverting nothing.
          'actions' => ['add_translation', 'delete', 'disable', 'download', 'enable', 'update'],
          'classes' => ['table', 'table-striped'],
          'toolbar' => [
            [
              'entity' => 'MessageTemplate',
              'action' => 'add',
              'style' => 'primary',
              'text' => E::ts('Add Message Template'),
              'icon' => 'fa-plus',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'msg_title',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'msg_subject',
              'label' => E::ts('Subject'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'label' => E::ts('Enabled'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'menu',
              'alignment' => 'text-right',
              'text' => '',
              'icon' => 'fa-bars',
              'style' => 'default',
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'MessageTemplate',
                  'action' => 'update',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                ],
                [
                  'task' => 'enable',
                  'icon' => 'fa-toggle-on',
                  'text' => E::ts('Enable'),
                  'style' => 'default',
                  'conditions' => [['is_active', '=', FALSE]],
                ],
                [
                  'task' => 'disable',
                  'icon' => 'fa-toggle-off',
                  'text' => E::ts('Disable'),
                  'style' => 'default',
                  'conditions' => [['is_active', '=', TRUE]],
                ],
                [
                  'task' => 'delete',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  // A link naming a task is rendered whether or not the task is available
                  // to this user, so the task's own permission has to be restated here.
                  'conditions' => [['check user permission', '=', ['administer CiviCRM']]],
                ],
              ],
            ],
          ],
          'cssRules' => [
            ['disabled', 'is_active', '=', FALSE],
          ],
        ],
      ],
      'match' => ['name'],
    ],
  ],
];
