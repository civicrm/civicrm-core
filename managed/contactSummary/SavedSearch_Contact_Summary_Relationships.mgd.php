<?php

// Conditionally add join only if CiviCase is enabled
$civiCaseEnabled = CRM_Core_Component::isEnabled('CiviCase');
$joins = [
  [
    'Contact AS RelationshipCache_Contact_far_contact_id_01',
    'LEFT',
    ['far_contact_id', '=', 'RelationshipCache_Contact_far_contact_id_01.id'],
  ],
];
$links = [
  [
    'entity' => 'Relationship',
    'action' => 'view',
    'join' => '',
    'target' => 'crm-popup',
    'icon' => 'fa-external-link',
    'text' => ts('View Relationship'),
    'style' => 'default',
    'path' => '',
    'task' => '',
    'condition' => [],
  ],
  [
    'entity' => 'Relationship',
    'action' => 'update',
    'join' => '',
    'target' => 'crm-popup',
    'icon' => 'fa-pencil',
    'text' => ts('Update Relationship'),
    'style' => 'default',
    'path' => '',
    'task' => '',
    'condition' => [],
  ],
  [
    'task' => 'disable',
    'entity' => 'Relationship',
    'join' => '',
    'target' => 'crm-popup',
    'icon' => 'fa-toggle-off',
    'text' => ts('Disable Relationship'),
    'style' => 'default',
    'path' => '',
    'action' => '',
    'condition' => [],
  ],
  [
    'entity' => 'Relationship',
    'action' => 'delete',
    'join' => '',
    'target' => 'crm-popup',
    'icon' => 'fa-trash',
    'text' => ts('Delete Relationship'),
    'style' => 'danger',
    'path' => '',
    'task' => '',
    'condition' => [],
  ],
];
if ($civiCaseEnabled) {
  $joins[] = [
    'Case AS RelationshipCache_Case_case_id_01',
    'LEFT',
    ['case_id', '=', 'RelationshipCache_Case_case_id_01.id'],
  ];
  $links[] = [
    'entity' => 'Case',
    'action' => 'view',
    'join' => 'RelationshipCache_Case_case_id_01',
    'target' => '',
    'icon' => 'fa-folder-open',
    'text' => ts('Manage Case'),
    'style' => 'default',
    'condition' => [],
  ];
}

return [
  [
    'name' => 'SavedSearch_Contact_Summary_Relationships',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Relationships',
        'label' => ts('Contact Summary Relationships'),
        'api_entity' => 'RelationshipCache',
        'api_params' => [
          'version' => 4,
          'select' => [
            'near_relation:label',
            'RelationshipCache_Contact_far_contact_id_01.display_name',
            'start_date',
            'end_date',
            'RelationshipCache_Contact_far_contact_id_01.address_primary.city',
            'RelationshipCache_Contact_far_contact_id_01.address_primary.state_province_id:label',
            'RelationshipCache_Contact_far_contact_id_01.email_primary.email',
            'RelationshipCache_Contact_far_contact_id_01.phone_primary.phone',
            'permission_near_to_far:label',
            'permission_far_to_near:label',
            'is_active',
          ],
          'orderBy' => [],
          'where' => [
            ['RelationshipCache_Contact_far_contact_id_01.is_deleted', '=', FALSE],
          ],
          'groupBy' => [],
          'join' => $joins,
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Contact_Summary_Relationships_SearchDisplay_Contact_Summary_Relationships_Tab',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Relationships_Active',
        'label' => ts('Contact Summary Relationships Active'),
        'saved_search_id.name' => 'Contact_Summary_Relationships',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
            'expose_limit' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'near_relation:label',
              'label' => ts('Relationship'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'field' => 'permission_far_to_near:icon',
                  'side' => 'left',
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.display_name',
              'label' => ts('With'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'field' => 'RelationshipCache_Contact_far_contact_id_01.contact_sub_type:icon',
                  'side' => 'left',
                ],
                [
                  'field' => 'RelationshipCache_Contact_far_contact_id_01.contact_type:icon',
                  'side' => 'left',
                ],
                [
                  'field' => 'permission_near_to_far:icon',
                  'side' => 'right',
                ],
              ],
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'RelationshipCache_Contact_far_contact_id_01',
                'target' => '',
              ],
              'title' => ts('View Related Contact'),
            ],
            [
              'type' => 'field',
              'key' => 'start_date',
              'label' => ts('Dates'),
              'sortable' => TRUE,
              'rewrite' => '[start_date] - [end_date]',
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.address_primary.city',
              'label' => ts('City'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.address_primary.state_province_id:label',
              'label' => ts('State/Prov'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.email_primary.email',
              'label' => ts('Email'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'icon' => 'fa-ban',
                  'side' => 'left',
                  'if' => [
                    'RelationshipCache_Contact_far_contact_id_01.do_not_email',
                    '=',
                    TRUE,
                  ],
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.phone_primary.phone',
              'label' => ts('Phone'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'icon' => 'fa-ban',
                  'side' => 'left',
                  'if' => [
                    'RelationshipCache_Contact_far_contact_id_01.do_not_phone',
                    '=',
                    TRUE,
                  ],
                ],
              ],
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'links' => $links,
              'type' => 'menu',
              'label' => ts('Row Actions'),
              'label_hidden' => TRUE,
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
          ],
          'toolbar' => [
            [
              'action' => 'add',
              'entity' => 'Relationship',
              'text' => ts('Add Relationship'),
              'icon' => 'fa-plus',
              'style' => 'primary',
              'target' => 'crm-popup',
              'join' => '',
              'path' => '',
              'task' => '',
              'condition' => [],
            ],
          ],
        ],
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Contact_Summary_Relationships_SearchDisplay_Contact_Summary_Relationships_Inactive',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Relationships_Inactive',
        'label' => ts('Contact Summary Relationships Inactive'),
        'saved_search_id.name' => 'Contact_Summary_Relationships',
        'type' => 'table',
        'settings' => [
          'description' => '',
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
            'expose_limit' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'near_relation:label',
              'label' => ts('Relationship'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'field' => 'permission_far_to_near:icon',
                  'side' => 'left',
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.display_name',
              'label' => ts('With'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'field' => 'RelationshipCache_Contact_far_contact_id_01.contact_sub_type:icon',
                  'side' => 'left',
                ],
                [
                  'field' => 'RelationshipCache_Contact_far_contact_id_01.contact_type:icon',
                  'side' => 'left',
                ],
                [
                  'field' => 'permission_near_to_far:icon',
                  'side' => 'right',
                ],
              ],
              'link' => [
                'path' => '',
                'entity' => 'Contact',
                'action' => 'view',
                'join' => 'RelationshipCache_Contact_far_contact_id_01',
                'target' => '',
              ],
              'title' => ts('View Related Contact'),
            ],
            [
              'type' => 'field',
              'key' => 'start_date',
              'label' => ts('Dates'),
              'sortable' => TRUE,
              'rewrite' => '[start_date] - [end_date]',
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.address_primary.city',
              'label' => ts('City'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.address_primary.state_province_id:label',
              'label' => ts('State/Prov'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.email_primary.email',
              'label' => ts('Email'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'icon' => 'fa-ban',
                  'side' => 'left',
                  'if' => [
                    'RelationshipCache_Contact_far_contact_id_01.do_not_email',
                    '=',
                    TRUE,
                  ],
                ],
              ],
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.phone_primary.phone',
              'label' => ts('Phone'),
              'sortable' => TRUE,
              'icons' => [
                [
                  'icon' => 'fa-ban',
                  'side' => 'left',
                  'if' => [
                    'RelationshipCache_Contact_far_contact_id_01.do_not_phone',
                    '=',
                    TRUE,
                  ],
                ],
              ],
            ],
            [
              'text' => '',
              'style' => 'default',
              'size' => 'btn-xs',
              'icon' => 'fa-bars',
              'label' => ts('Row Actions'),
              'label_hidden' => TRUE,
              'links' => [
                [
                  'entity' => 'Relationship',
                  'action' => 'view',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-external-link',
                  'text' => ts('View Relationship'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'Relationship',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => ts('Update Relationship'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
                [
                  'task' => 'enable',
                  'entity' => 'Relationship',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-on',
                  'text' => ts('Enable Relationship'),
                  'style' => 'default',
                  'path' => '',
                  'action' => '',
                  'condition' => [],
                ],
                [
                  'entity' => 'Relationship',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => ts('Delete Relationship'),
                  'style' => 'danger',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'menu',
              'alignment' => 'text-right',
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
            'disabled',
          ],
        ],
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
