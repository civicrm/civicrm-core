<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Headers_Footers_and_Automated_Messages',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Headers_Footers_and_Automated_Messages',
        'label' => E::ts('Headers, Footers, and Automated Messages'),
        'api_entity' => 'MailingComponent',
        'api_params' => [
          'version' => 4,
          'select' => [
            'name',
            'component_type:label',
            'subject',
            'body_html',
            'body_text',
            'is_default',
            'is_active',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Headers_Footers_and_Automated_Messages_SearchDisplay_Headers_Footers_and_Automated_Messages_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Headers_Footers_and_Automated_Messages_Table_1',
        'label' => E::ts('Headers, Footers, and Automated Messages'),
        'saved_search_id.name' => 'Headers_Footers_and_Automated_Messages',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'component_type',
              'ASC',
            ],
          ],
          'limit' => 50,
          'pager' => [],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'label' => E::ts('Name'),
              'sortable' => FALSE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'component_type:label',
              'label' => E::ts('Type'),
              'sortable' => FALSE,
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'label' => E::ts('Subject'),
              'sortable' => FALSE,
            ],
            [
              'type' => 'field',
              'key' => 'body_html',
              'label' => E::ts('Body HTML'),
              'sortable' => FALSE,
            ],
            [
              'type' => 'field',
              'key' => 'body_text',
              'label' => E::ts('Body Text'),
              'sortable' => FALSE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'label' => E::ts('Enabled'),
              'sortable' => FALSE,
              'icons' => [],
              'rewrite' => '',
            ],
            [
              'type' => 'field',
              'key' => 'is_default',
              'label' => E::ts('Default'),
              'sortable' => FALSE,
              'title' => NULL,
              'rewrite' => '[none]',
              'icons' => [
                [
                  'icon' => 'fa-check',
                  'side' => 'left',
                  'if' => ['is_default', '=', TRUE],
                ],
              ],
            ],
            [
              'links' => [
                [
                  'entity' => 'MailingComponent',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => '',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'task' => 'disable',
                  'entity' => 'MailingComponent',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => '',
                  'text' => E::ts('Disable'),
                  'style' => 'default',
                  'path' => '',
                  'action' => '',
                  'condition' => [],
                ],
                [
                  'task' => 'enable',
                  'entity' => 'MailingComponent',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => '',
                  'text' => E::ts('Enable'),
                  'style' => 'default',
                  'path' => '',
                  'action' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'links',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table-striped',
            'table',
            'crm-sticky-header',
          ],
          'toolbar' => [
            [
              'action' => 'add',
              'entity' => 'MailingComponent',
              'text' => E::ts('Add Mailing Component'),
              'icon' => 'fa-plus',
              'style' => 'primary',
              'target' => 'crm-popup',
              'join' => '',
              'path' => '',
              'task' => '',
              'condition' => [
                'check user permission',
                '=',
                [
                  'access CiviMail',
                ],
              ],
            ],
          ],
          'cssRules' => [
            [
              'disabled',
              'is_active',
              '=',
              FALSE,
            ],
          ],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
