<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

if (!CRM_Core_Component::isEnabled('CiviMail')) {
  return [];
}

$select = [
  'id',
  'name',
  'language:label',
  'created_id.display_name',
  'created_date',
  'scheduled_id.display_name',
  'status:label',
];

$columns = [
  [
    'type' => 'field',
    'key' => 'name',
    'label' => 'Mailing Name',
    'sortable' => TRUE,
    'icons' => [],
  ],
  [
    'type' => 'field',
    'key' => 'status:label',
    'label' => 'Status',
    'sortable' => TRUE,
    'icons' => [],
    'cssRules' => [],
  ],
];

// language only if multilingual
if (CRM_Core_I18n::isMultilingual()) {
  $columns[] = [
    'type' => 'field',
    'key' => 'language:label',
    'label' => E::ts('Language'),
    'sortable' => TRUE,
  ];
}

$columns = array_merge($columns, [
  [
    'type' => 'field',
    'key' => 'created_id.display_name',
    'label' => 'Created By',
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'created_date',
    'label' => 'Created Date',
    'sortable' => TRUE,
  ],
]);

// campaign only if component is enabled
if (CRM_Core_Component::isEnabled('CiviCampaign')) {
  $select[] = 'campaign_id:label';
  $columns[] = [
    'type' => 'field',
    'key' => 'campaign_id:label',
    'label' => E::ts('Campaign'),
    'sortable' => TRUE,
  ];
}

$columns = array_merge($columns, [
  [
    'text' => '',
    'type' => 'menu',
    'alignment' => 'text-right',
    'style' => 'default',
    'size' => 'btn-xs',
    'icon' => 'fa-bars',
    'links' => [
      [
        'entity' => 'Mailing',
        'action' => 'update',
        'join' => '',
        'target' => '',
        'icon' => 'fa-pencil',
        'text' => 'Continue',
        'style' => 'default',
        'path' => '',
      ],
      [
        'icon' => 'fa-clone',
        'text' => 'Copy',
        'style' => 'default',
        'condition' => [
          'status:name',
          'NOT IN',
          ['Paused', 'Scheduled', 'Running'],
        ],
        'entity' => 'Mailing',
        'action' => 'copy',
        'join' => '',
        'target' => '',
      ],
      [
        'path' => 'civicrm/mailing/action?action=reopen&mid=[id]&reset=1',
        'icon' => 'fa-play',
        'text' => 'Resume',
        'style' => 'default',
        'condition' => [
          'status:name',
          '=',
          'Paused',
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => 'crm-popup',
      ],
      [
        'path' => 'civicrm/mailing/action?action=disable&mid=[id]&reset=1',
        'icon' => 'fa-ban',
        'text' => 'Cancel',
        'style' => 'default',
        'condition' => [
          'status:name',
          'IN',
          ['Scheduled', 'Running'],
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => 'crm-popup',
      ],
      [
        'entity' => 'Mailing',
        'action' => 'preview',
        'join' => '',
        'target' => 'crm-popup',
        'icon' => 'fa-eye',
        'text' => 'Preview Mailing',
        'style' => 'default',
        'path' => '',
        'condition' => [],
      ],
      [
        'path' => 'civicrm/mailing/action?action=disable&mid=[id]&reset=1',
        'icon' => 'fa-ban',
        'text' => 'Cancel',
        'style' => 'default',
        'condition' => [
          'status:name',
          '=',
          'Paused',
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => 'crm-popup',
      ],
      [
        'entity' => 'Mailing',
        'task' => 'delete',
        'join' => '',
        'target' => 'crm-popup',
        'icon' => 'fa-trash',
        'text' => 'Delete',
        'style' => 'danger',
        'condition' => [],
      ],
    ],
  ],
]);

return [
  [
    'name' => 'SavedSearch_Draft_Mailings_Browse',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Draft_Mailings_Browse',
        'label' => E::ts('Draft Mailings'),
        'api_entity' => 'Mailing',
        'api_params' => [
          'version' => 4,
          'select' => $select,
          'orderBy' => [],
          'where' => [
            ['sms_provider_id', 'IS EMPTY'],
            ['domain_id:name', '=', 'current_domain'],
            ['status:name', '=', 'Draft'],
          ],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'MailingJob AS Mailing_MailingJob_mailing_id_01',
              'LEFT',
              ['id', '=', 'Mailing_MailingJob_mailing_id_01.mailing_id'],
              ["Mailing_MailingJob_mailing_id_01.is_test", "=", FALSE],
            ],
          ],
          'having' => [],
        ],
        'description' => NULL,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Draft_Mailings_Browse_SearchDisplay_Mailings_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Draft_Mailings_Table',
        'label' => E::ts('Draft Mailings'),
        'saved_search_id.name' => 'Draft_Mailings_Browse',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'created_date',
              'DESC',
            ],
          ],
          'limit' => 50,
          'pager' => [
            'show_count' => FALSE,
            'expose_limit' => FALSE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => $columns,
          'actions' => TRUE,
          'classes' => [
            'table',
            'table-striped',
          ],
          'cssRules' => [
            [
              'bg-warning',
              'status:name',
              '=',
              'Paused',
            ],
            [
              'disabled',
              'status:name',
              '=',
              'Canceled',
            ],
            [
              'bg-success',
              'status:name',
              'IN',
              ['Scheduled', 'Running'],
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'Mailing',
              'action' => 'add',
              'text' => 'Add Mailing',
              'icon' => 'fa-plus',
              'style' => 'primary',
              'target' => '',
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
