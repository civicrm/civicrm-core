<?php

use CRM_CivicrmAdminUi_ExtensionUtil as E;

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
    'key' => 'Mailing_MailingJob_mailing_id_01.status:label',
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
if (CRM_Campaign_BAO_Campaign::isComponentEnabled()) {
  $columns[] = [
    'type' => 'field',
    'key' => 'campaign_id:title',
    'dataType' => 'String',
    'label' => E::ts('Language'),
    'sortable' => TRUE,
  ];
}

$columns = array_merge($columns, [
  [
    'links' => [
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
          'Mailing_MailingJob_mailing_id_01.status:label',
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
          'Mailing_MailingJob_mailing_id_01.status:label',
          'IN',
          [
            'Scheduled',
            'Running',
          ],
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => '',
      ],
      [
        'path' => 'civicrm/mailing/send?mid=[id]&reset=1',
        'icon' => 'fa-external-link',
        'text' => E::ts('Copy'),
        'style' => 'default',
        'condition' => [
          'Mailing_MailingJob_mailing_id_01.status:label',
          'NOT IN',
          [
            'Paused',
            'Scheduled',
            'Running',
          ],
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => '',
      ],
    ],
    'type' => 'links',
    'alignment' => 'text-right',
  ],
  [
    'text' => '',
    'style' => 'default',
    'size' => 'btn-xs',
    'icon' => 'fa-bars',
    'links' => [
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
          'Mailing_MailingJob_mailing_id_01.status:label',
          'IN',
          [
            'Scheduled',
            'Running',
          ],
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
          'Mailing_MailingJob_mailing_id_01.status:label',
          'IN',
          [
            'Paused',
          ],
        ],
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => '',
      ],
      [
        'entity' => '',
        'action' => '',
        'join' => '',
        'target' => '',
        'icon' => 'fa-trash',
        'text' => E::ts('Delete'),
        'style' => 'danger',
        'path' => 'civicrm/mailing/browse?action=delete&mid=[id]&reset=1',
        'condition' => [],
      ],
    ],
    'type' => 'menu',
    'alignment' => 'text-right',
  ],
]);


return [
  [
    'name' => 'SavedSearch_Mailings_Browse_Scheduled',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailings_Browse_Scheduled',
        'label' => E::ts('Mailings Browse Scheduled'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Mailing',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'language:label',
            'created_id.display_name',
            'created_date',
            'scheduled_id.display_name',
            'Mailing_MailingJob_mailing_id_01.scheduled_date',
            'Mailing_MailingJob_mailing_id_01.start_date',
            'Mailing_MailingJob_mailing_id_01.end_date',
            'Mailing_MailingJob_mailing_id_01.status:label',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'MailingJob AS Mailing_MailingJob_mailing_id_01',
              'INNER',
              [
                'id',
                '=',
                'Mailing_MailingJob_mailing_id_01.mailing_id',
              ],
            ],
          ],
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
    'name' => 'SavedSearch_Mailings_Browse_Scheduled_SearchDisplay_Mailings_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailings_Table',
        'label' => E::ts('Mailings Table'),
        'saved_search_id.name' => 'Mailings_Browse_Scheduled',
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
          'pager' => [],
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
              'Mailing_MailingJob_mailing_id_01.status:name',
              '=',
              'Paused',
            ],
            [
              'disabled',
              'Mailing_MailingJob_mailing_id_01.status:name',
              '=',
              'Canceled',
            ],
            [
              'bg-success',
              'Mailing_MailingJob_mailing_id_01.status:name',
              'IN',
              [
                'Scheduled',
                'Running',
              ],
            ],
          ],
          'addButton' => [
            'path' => 'civicrm/mailing/send',
            'text' => E::ts('Add Mailing'),
            'icon' => 'fa-plus',
            'target' => '',
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
