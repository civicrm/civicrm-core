<?php
use CRM_MessageAdmin_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Message_Templates_Workflow',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Message_Templates_Workflow',
        'label' => E::ts('System Workflow Messages'),
        'api_entity' => 'MessageTemplate',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'msg_title',
            // `master_id` is a correlated subquery, which MySQL rejects alongside GROUP BY
            // under ONLY_FULL_GROUP_BY. It also cannot be aliased back to its own field name.
            'MAX(master_id) AS customized',
            // Both aggregates concatenate the same raw column, so `:label` is applied per
            // value afterwards and the two arrays stay index-aligned for the per-language link.
            'GROUP_CONCAT(DISTINCT tx.language:label) AS translation_languages',
            'GROUP_CONCAT(DISTINCT tx.language) AS translation_codes',
          ],
          'orderBy' => [],
          'where' => [
            ['workflow_name', 'IS NOT EMPTY'],
            ['is_reserved', '=', FALSE],
          ],
          'groupBy' => ['id'],
          'join' => [
            [
              'Translation AS tx',
              'LEFT',
              NULL,
              ['tx.entity_table', '=', "'civicrm_msg_template'"],
              ['tx.entity_id', '=', 'id'],
            ],
          ],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_Message_Templates_Workflow_SearchDisplay_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Message_Templates_Workflow_Table',
        'label' => E::ts('System Workflow Messages'),
        'saved_search_id.name' => 'Message_Templates_Workflow',
        'type' => 'table',
        'settings' => [
          'limit' => 50,
          'sort' => [['msg_title', 'ASC']],
          'pager' => ['show_count' => TRUE, 'expose_limit' => TRUE],
          'placeholder' => 5,
          // A workflow template is looked up by `workflow_name` alone when it is sent, so
          // disabling one would not stop it and deleting one would leave the workflow with
          // nothing to send. Listing the tasks leaves those two out of the bulk menu.
          'actions' => ['add_translation', 'revert', 'update', 'download'],
          'classes' => ['table', 'table-striped'],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'msg_title',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'translation_languages',
              'label' => E::ts('Translations'),
              // A link on a multi-valued field is built per value, so each language links to
              // its own translation rather than one link naming every language at once.
              'link' => [
                'path' => 'civicrm/admin/messageTemplates/edit#/edit?id=[id]&lang=[translation_codes]',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'customized',
              'label' => E::ts('Modified'),
              // The aggregate holds the id of the packaged original, which means nothing to a
              // reader - `empty_value` covers the unmodified rows and `rewrite` the rest.
              'empty_value' => E::ts('No'),
              'rewrite' => E::ts('Yes'),
              'title' => E::ts('Whether this template has been changed from the packaged default'),
              'cssRules' => [
                ['font-italic', 'customized', 'IS EMPTY'],
              ],
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
                  'task' => 'add_translation',
                  'icon' => 'fa-language',
                  'text' => E::ts('Add Translation'),
                  'style' => 'default',
                  // A link naming a task is rendered whether or not the task is available
                  // to this user, so the task's own permission has to be restated here.
                  'conditions' => [['check user permission', '=', ['translate CiviCRM']]],
                ],
                [
                  'path' => 'civicrm/admin/messageTemplates/edit#/edit?id=[id]',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                ],
                [
                  'task' => 'revert',
                  'icon' => 'fa-undo',
                  'text' => E::ts('Revert to Default'),
                  'style' => 'default',
                  // Only a template that differs from its packaged original has anything to
                  // revert to, and `revert` falls back to the default permission.
                  'conditions' => [
                    ['customized', 'IS NOT EMPTY'],
                    ['check user permission', '=', ['administer CiviCRM']],
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
      'match' => ['name'],
    ],
  ],
];
