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
        'form_values' => [
          'join' => [
            'MessageTemplate_Translation_entity_id_01' => E::ts('Any Translation'),
            'MessageTemplate_Translation_entity_id_02' => E::ts('Active Translation'),
            'MessageTemplate_Translation_entity_id_03' => E::ts('Draft Translation'),
          ],
        ],
        'api_entity' => 'MessageTemplate',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'msg_title',
            // `master_id` is a correlated subquery, which MySQL rejects alongside GROUP BY
            // under ONLY_FULL_GROUP_BY. It also cannot be aliased back to its own field name.
            'MAX(master_id) AS customized',
            // Each pair concatenates the same raw column in the same order, so `:label` is
            // applied per value afterwards and the two arrays stay index-aligned for the
            // per-language link.
            'GROUP_CONCAT(DISTINCT MessageTemplate_Translation_entity_id_02.language:label ORDER BY MessageTemplate_Translation_entity_id_02.language ASC) AS translation_languages',
            'GROUP_CONCAT(DISTINCT MessageTemplate_Translation_entity_id_02.language ORDER BY MessageTemplate_Translation_entity_id_02.language ASC) AS translation_codes',
            'GROUP_CONCAT(DISTINCT MessageTemplate_Translation_entity_id_03.language:label ORDER BY MessageTemplate_Translation_entity_id_03.language ASC) AS draft_languages',
            'GROUP_CONCAT(DISTINCT MessageTemplate_Translation_entity_id_03.language ORDER BY MessageTemplate_Translation_entity_id_03.language ASC) AS draft_codes',
          ],
          'orderBy' => [],
          'where' => [
            ['workflow_name', 'IS NOT EMPTY'],
            ['is_reserved', '=', FALSE],
          ],
          'groupBy' => ['id'],
          'join' => [
            // The SearchKit editor only recognises join aliases in its own `<join>_NN` form, and
            // expects condition values JSON-quoted. _01 is read only by the language filter, so
            // filtering matches drafts as well as active translations without narrowing the
            // languages the columns list; _02 feeds Translations and _03 Drafts.
            [
              'Translation AS MessageTemplate_Translation_entity_id_01',
              'LEFT',
              NULL,
              ['MessageTemplate_Translation_entity_id_01.entity_table', '=', "'civicrm_msg_template'"],
              ['MessageTemplate_Translation_entity_id_01.entity_id', '=', 'id'],
            ],
            [
              'Translation AS MessageTemplate_Translation_entity_id_02',
              'LEFT',
              NULL,
              ['MessageTemplate_Translation_entity_id_02.entity_table', '=', "'civicrm_msg_template'"],
              ['MessageTemplate_Translation_entity_id_02.entity_id', '=', 'id'],
              ['MessageTemplate_Translation_entity_id_02.status_id:name', '=', '"active"'],
            ],
            [
              'Translation AS MessageTemplate_Translation_entity_id_03',
              'LEFT',
              NULL,
              ['MessageTemplate_Translation_entity_id_03.entity_table', '=', "'civicrm_msg_template'"],
              ['MessageTemplate_Translation_entity_id_03.entity_id', '=', 'id'],
              ['MessageTemplate_Translation_entity_id_03.status_id:name', '=', '"draft"'],
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
              'key' => 'draft_languages',
              'label' => E::ts('Drafts'),
              'title' => E::ts('Languages with an unpublished draft'),
              'link' => [
                'path' => 'civicrm/admin/messageTemplates/edit#/edit?id=[id]&lang=[draft_codes]&status=draft',
              ],
              'icons' => [
                ['icon' => 'fa-file-text-o', 'side' => 'left', 'if' => ['draft_languages', 'IS NOT EMPTY']],
              ],
              'cssRules' => [
                ['text-warning', 'draft_languages', 'IS NOT EMPTY'],
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
