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
    'size' => 'btn-xs',
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
        'condition' => [],
      ],
    ],
    'type' => 'buttons',
    'alignment' => 'text-right',
  ],
  [
    'text' => '',
    'style' => 'secondary',
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
        'path' => 'civicrm/mailing/send?mid=[id]&reset=1',
        'icon' => 'fa-external-link',
        'text' => E::ts('Copy'),
        'style' => 'default',
        'condition' => [],
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
    'name' => 'SavedSearch_Mailings_Browse_Unscheduled',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailings_Browse_Unscheduled',
        'label' => E::ts('Mailings Browse Unscheduled'),
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
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'MailingJob AS Mailing_MailingJob_mailing_id_01',
              'EXCLUDE',
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
    'name' => 'SavedSearch_Mailings_Browse_Unscheduled_SearchDisplay_Mailings_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Mailings_Table',
        'label' => E::ts('Mailings Table'),
        'saved_search_id.name' => 'Mailings_Browse_Unscheduled',
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
          'cssRules' => [],
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
