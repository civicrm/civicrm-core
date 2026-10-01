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
            // A document-upload template (.docx/.odt) has no on-screen editor yet, so it keeps
            // using the classic edit form - this is non-empty only for those rows.
            'MAX(MessageTemplate_EntityFile_File_01.id) AS has_document',
            // Raw tag ids, not labels - the crm-entity-tags widget looks up label/color
            // itself and needs the ids to know which tags are already applied.
            'GROUP_CONCAT(DISTINCT MessageTemplate_EntityTag_Tag_01.id) AS tag_ids',
          ],
          'orderBy' => [],
          'where' => [
            ['workflow_name', 'IS EMPTY'],
            ['is_reserved', '=', FALSE],
          ],
          'groupBy' => ['id'],
          // No explicit ON conditions needed on either join below - with no conditions given,
          // API4's bridge-join handling links the bridge row to this entity automatically
          // (entity_id/entity_table), same as the generic `tags` virtual field does elsewhere.
          'join' => [
            ['File AS MessageTemplate_EntityFile_File_01', 'LEFT', 'EntityFile'],
            ['Tag AS MessageTemplate_EntityTag_Tag_01', 'LEFT', 'EntityTag'],
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
        // The `has_document` join is a bridge entity (EntityFile) with no ACL delegate
        // registered for civicrm_msg_template, so it denies access under normal permission
        // checking. Visibility of this listing is already gated by the page's own
        // 'edit message templates' permission, so bypassing ACLs here exposes nothing new.
        'acl_bypass' => TRUE,
        'settings' => [
          'limit' => 50,
          'sort' => [['msg_title', 'ASC']],
          'pager' => ['show_count' => TRUE, 'expose_limit' => TRUE],
          'placeholder' => 5,
          // `revert` restores a template from the packaged original it was copied from, which
          // a user-driven template does not have, so it would report reverting nothing.
          // `tag` becomes available once MessageTemplate is registered in `tag_used_for`
          // (see CRM_Upgrade_Incremental_php_SixTwenty::registerMessageTemplateTagUsedFor()).
          'actions' => ['add_translation', 'delete', 'disable', 'download', 'enable', 'tag', 'update'],
          'classes' => ['table', 'table-striped'],
          'toolbar' => [
            [
              'path' => 'civicrm/admin/messageTemplates/edit#/edit',
              'style' => 'primary',
              'text' => E::ts('Add template'),
              'icon' => 'fa-plus',
            ],
            [
              // Same as MessageTemplate's declared `add` path, plus `docOnly=1` to hide the
              // now-redundant Source radio - see CRM_Admin_Form_MessageTemplates::preProcess().
              'path' => 'civicrm/admin/messageTemplates/add?action=add&reset=1&docOnly=1',
              'style' => 'default',
              'text' => E::ts('Create template from document'),
              'icon' => 'fa-file-word-o',
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
              'type' => 'include',
              'path' => '~/crmMsgadm/tagsColumn.html',
              'label' => E::ts('Tags'),
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
                  // Document-upload templates have no on-screen editor, so they keep using
                  // the classic edit form - see MessageTemplate's own declared `update` path.
                  'entity' => 'MessageTemplate',
                  'action' => 'update',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'conditions' => [['has_document', 'IS NOT EMPTY']],
                ],
                [
                  'path' => 'civicrm/admin/messageTemplates/edit#/edit?id=[id]',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'conditions' => [['has_document', 'IS EMPTY']],
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
