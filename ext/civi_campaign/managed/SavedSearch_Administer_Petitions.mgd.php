<?php
use CRM_Campaign_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Administer_Petitions',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Petitions',
        'label' => E::ts('Administer Petitions'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Survey',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'title',
            'campaign_id:label',
            'is_active',
            'is_default',
          ],
          'orderBy' => [],
          'where' => [
            ['activity_type_id:name', '=', 'Petition'],
          ],
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
    'name' => 'SavedSearch_Administer_Petitions_SearchDisplay_Petitions_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Petitions_Table',
        'label' => E::ts('Administer Petitions'),
        'saved_search_id.name' => 'Administer_Petitions',
        'type' => 'table',
        'settings' => [
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => FALSE,
            'expose_limit' => TRUE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'sort' => [
            ['is_active', 'DESC'],
            ['title', 'ASC'],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'title',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'campaign_id:label',
              'label' => E::ts('Survey'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_default',
              'label' => E::ts('Default'),
              'sortable' => TRUE,
              'rewrite' => ' ',
              'icons' => [
                [
                  'icon' => 'fa-check-square-o',
                  'side' => 'left',
                  'if' => [
                    'is_default',
                    '=',
                    TRUE,
                  ],
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'label' => E::ts('Enabled'),
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'type' => 'buttons',
              'alignment' => 'text-right',
              'links' => [
                [
                  'path' => 'civicrm/petition/add?reset=1&action=update&id=[id]',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'condition' => [],
                  'target' => 'crm-popup',
                ],
                [
                  'task' => 'enable',
                  'entity' => 'Survey',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-on',
                  'text' => E::ts('Enable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'task' => 'disable',
                  'entity' => 'Survey',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-off',
                  'text' => E::ts('Disable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'entity' => 'Survey',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger small-popup',
                  'path' => '',
                  'condition' => [],
                ],
              ],
            ],
            [
              'text' => E::ts('Signatures'),
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'type' => 'menu',
              'alignment' => 'text-right',
              'links' => [
                [
                  'path' => 'civicrm/petition/sign?reset=1&sid=[id]',
                  'icon' => 'fa-clipboard',
                  'text' => E::ts('Sign'),
                  'style' => 'default',
                  'condition' => [],
                  'target' => '_blank',
                ],
                [
                  'path' => 'civicrm/activity/search?force=1&survey=[id]',
                  'icon' => 'fa-list-alt',
                  'text' => E::ts('View Signatures'),
                  'style' => 'default',
                  'condition' => [],
                  'target' => '_blank',
                ],
              ],
            ],
          ],
          'toolbar' => [
            [
              'path' => 'civicrm/petition/add?reset=1',
              'text' => E::ts('Add Petition'),
              'target' => 'crm-popup',
              'icon' => 'fa-plus',
              'style' => 'primary',
              'condition' => [
                'check user permission',
                'CONTAINS',
                ['administer CiviCampaign', 'manage campaign'],
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
        'acl_bypass' => FALSE,
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
