<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

if (!CRM_Core_Component::isEnabled('CiviMail')) {
  return [];
}

$columns = [
  [
    'type' => 'field',
    'key' => 'name',
    'dataType' => 'String',
    'label' => E::ts('Mailing Name'),
    'sortable' => TRUE,
    'icons' => [],
  ],
  [
    'type' => 'field',
    'key' => 'status:label',
    'dataType' => 'String',
    'label' => E::ts('Status'),
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
    'dataType' => 'String',
    'label' => E::ts('Language'),
    'sortable' => TRUE,
  ];
}

$columns = array_merge($columns, [
  [
    'type' => 'field',
    'key' => 'created_id.display_name',
    'dataType' => 'String',
    'label' => E::ts('Created By'),
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'created_date',
    'dataType' => 'Timestamp',
    'label' => E::ts('Created Date'),
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'scheduled_id.display_name',
    'dataType' => 'String',
    'label' => E::ts('Sent By'),
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'Mailing_MailingJob_mailing_id_01.scheduled_date',
    'dataType' => 'Timestamp',
    'label' => E::ts('Scheduled'),
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'Mailing_MailingJob_mailing_id_01.start_date',
    'dataType' => 'Timestamp',
    'label' => E::ts('Started'),
    'sortable' => TRUE,
  ],
  [
    'type' => 'field',
    'key' => 'Mailing_MailingJob_mailing_id_01.end_date',
    'dataType' => 'Timestamp',
    'label' => E::ts('Completed'),
    'sortable' => TRUE,
  ],
]);

// campaign only if component is enabled
if (CRM_Core_Component::isEnabled('CiviCampaign')) {
  $columns[] = [
    'type' => 'field',
    'key' => 'campaign_id:label',
    'dataType' => 'String',
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
        'text' => E::ts('Continue'),
        'style' => 'default',
        'path' => '',
        'condition' => [
          'status',
          '=',
          'Draft',
        ],
      ],
      [
        'icon' => 'fa-clone',
        'text' => E::ts('Copy'),
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
        'entity' => 'Mailing',
        'action' => 'view',
        'join' => '',
        'target' => 'crm-popup',
        'icon' => 'fa-bar-chart',
        'text' => E::ts('Report'),
        'style' => 'default',
        'path' => '',
        'condition' => [],
      ],
      [
        'path' => 'civicrm/mailing/browse?action=reopen&mid=[id]&reset=1',
        'icon' => 'fa-play',
        'text' => E::ts('Resume'),
        'style' => 'default',
        'condition' => [
          'status:name',
          '=',
          'Paused',
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => '',
      ],
      [
        'path' => 'civicrm/mailing/browse?action=disable&mid=[id]&reset=1',
        'icon' => 'fa-ban',
        'text' => E::ts('Cancel'),
        'style' => 'default',
        'condition' => [
          'status:name',
          'IN',
          ['Scheduled', 'Running'],
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => '',
      ],
      [
        'entity' => 'Mailing',
        'action' => 'preview',
        'join' => '',
        'target' => 'crm-popup',
        'icon' => 'fa-eye',
        'text' => E::ts('Preview Mailing'),
        'style' => 'default',
        'path' => '',
        'condition' => [],
      ],
      [
        'path' => 'civicrm/mailing/browse?action=close&mid=[id]&reset=1',
        'icon' => 'fa-pause',
        'text' => E::ts('Pause'),
        'style' => 'default',
        'condition' => [
          'status:name',
          'IN',
          ['Scheduled', 'Running'],
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => '',
      ],
      [
        'path' => 'civicrm/mailing/browse?action=disable&mid=[id]&reset=1',
        'icon' => 'fa-ban',
        'text' => E::ts('Cancel'),
        'style' => 'default',
        'condition' => [
          'status:name',
          '=',
          'Paused',
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => '',
      ],
      [
        'entity' => 'Mailing',
        'action' => 'delete',
        'join' => '',
        'target' => 'crm-popup',
        'icon' => 'fa-trash',
        'text' => E::ts('Delete'),
        'style' => 'danger',
        'path' => 'civicrm/mailing/browse?action=delete&mid=[id]&reset=1',
        'condition' => [],
      ],
    ],
  ],
]);

return [
  [
    'name' => 'SavedSearch_Mailings_Browse',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailings_Browse',
        'label' => E::ts('Mailings'),
        'api_entity' => 'Mailing',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'language:label',
            'campaign_id:label',
            'created_id.display_name',
            'created_date',
            'scheduled_id.display_name',
            'Mailing_MailingJob_mailing_id_01.scheduled_date',
            'Mailing_MailingJob_mailing_id_01.start_date',
            'Mailing_MailingJob_mailing_id_01.end_date',
            'status:label',
          ],
          'orderBy' => [],
          'where' => [
            ['sms_provider_id', 'IS EMPTY'],
          ],
          'groupBy' => [],
          'join' => [
            [
              'MailingJob AS Mailing_MailingJob_mailing_id_01',
              'LEFT',
              ['id', '=', 'Mailing_MailingJob_mailing_id_01.mailing_id'],
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
    'name' => 'SavedSearch_Mailings_Browse_Scheduled_SearchDisplay_Mailings_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailings_Table',
        'label' => E::ts('Mailings'),
        'saved_search_id.name' => 'Mailings_Browse',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'Mailing_MailingJob_mailing_id_01.scheduled_date',
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
              'text' => E::ts('Add Mailing'),
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
