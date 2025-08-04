<?php
use CRM_UserDashboard_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_UserDashboard_Relationships',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Relationships',
        'label' => E::ts('User Dashboard - Relationships'),
        'api_entity' => 'RelationshipCache',
        'api_params' => [
          'version' => 4,
          'select' => [
            'near_relation:label',
            'RelationshipCache_Contact_far_contact_id_01.display_name',
            'start_date',
            'RelationshipCache_Contact_far_contact_id_01.address_primary.city',
            'RelationshipCache_Contact_far_contact_id_01.address_primary.state_province_id:label',
            'RelationshipCache_Contact_far_contact_id_01.email_primary.email',
            'RelationshipCache_Contact_far_contact_id_01.phone_primary.phone',
          ],
          'orderBy' => [],
          'where' => [
            ['near_contact_id', '=', 'user_contact_id'],
            ['is_current', '=', TRUE],
          ],
          'groupBy' => [],
          'join' => [
            [
              'Contact AS RelationshipCache_Contact_far_contact_id_01',
              'LEFT',
              ['far_contact_id', '=', 'RelationshipCache_Contact_far_contact_id_01.id'],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_UserDashboard_Relationships_SearchDisplay_UserDashboard_Relationships',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Relationships',
        'label' => E::ts('Your Contacts / Organizations'),
        'saved_search_id.name' => 'UserDashboard_Relationships',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 20,
          'pager' => [
            'hide_single' => TRUE,
            'expose_limit' => TRUE,
          ],
          'placeholder' => 1,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'near_relation:label',
              'dataType' => 'String',
              'label' => E::ts('Relationship'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.display_name',
              'dataType' => 'String',
              'label' => E::ts('With'),
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
              ],
            ],
            [
              'type' => 'field',
              'key' => 'start_date',
              'dataType' => 'Date',
              'label' => E::ts('Since'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.address_primary.city',
              'dataType' => 'String',
              'label' => E::ts('City'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.address_primary.state_province_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('State/Prov'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.email_primary.email',
              'dataType' => 'String',
              'label' => E::ts('Email'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'RelationshipCache_Contact_far_contact_id_01.phone_primary.phone',
              'dataType' => 'String',
              'label' => E::ts('Phone'),
              'sortable' => TRUE,
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
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
