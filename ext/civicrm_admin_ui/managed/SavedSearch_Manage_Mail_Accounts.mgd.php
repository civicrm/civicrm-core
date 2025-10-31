<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_mail extension.
if (!CRM_Core_Component::isEnabled('CiviMail')) {
  return [];
}

// This SearchDisplay shows an editable-in-place field for Enabled? for all rows, including the bounce processing mail account, which cannot actually be disabled (you can change it to No, but it won't actually be disabled). So this is FIXME for when we can set rows to edit-in-place conditionally.
return [
  [
    'name' => 'SavedSearch_Mail_Accounts',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mail_Accounts',
        'label' => E::ts('Mail Accounts'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'MailSettings',
        'api_params' => [
          'version' => 4,
          'select' => [
            'name',
            'server',
            'username',
            'localpart',
            'domain',
            'return_path',
            'protocol:label',
            'source',
            'is_ssl',
            'is_default',
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
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Mail_Accounts_SearchDisplay_Mail_Accounts_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mail_Accounts_Table_1',
        'label' => E::ts('Mail Accounts Table 1'),
        'saved_search_id.name' => 'Mail_Accounts',
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'server',
              'label' => E::ts('Server'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'username',
              'label' => E::ts('Username'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'localpart',
              'label' => E::ts('Localpart'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'domain',
              'label' => E::ts('Domain'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'return_path',
              'label' => E::ts('Return-Path'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'protocol:label',
              'label' => E::ts('Protocol'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'source',
              'label' => E::ts('Mail Folder'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_ssl',
              'label' => E::ts('Use SSL'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'html',
              'key' => 'is_default',
              'label' => E::ts('Used For'),
              'sortable' => TRUE,
              'editable' => TRUE,
              'rewrite' => '{if "[is_default]" == "' . E::ts('Yes') . '"}' . E::ts('Bounce Processing <strong>(Default)</strong>') . '{else}' . E::ts('Email-to-Activity') . '{/if}',
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => [
                [
                  'entity' => 'MailSettings',
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
                  'entity' => 'MailSettings',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'path' => '',
                  'condition' => [
                    'is_default',
                    '=',
                    FALSE,
                  ],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
          'toolbar' => [
            [
              'entity' => 'MailSettings',
              'action' => 'add',
              'target' => 'crm-popup',
              'style' => 'primary',
              'text' => E::ts('Add Mail Account'),
              'icon' => 'fa-plus',
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
