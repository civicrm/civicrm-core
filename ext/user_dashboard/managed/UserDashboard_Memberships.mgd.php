<?php
use CRM_UserDashboard_ExtensionUtil as E;

if (!CRM_Core_Component::isEnabled('CiviMember')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_UserDashboard_Memberships',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Memberships',
        'label' => E::ts('User Dashboard - Memberships'),
        'api_entity' => 'Membership',
        'api_params' => [
          'version' => 4,
          'select' => [
            'membership_type_id:label',
            'status_id:label',
            'start_date',
            'end_date',
            'join_date',
          ],
          'orderBy' => [],
          'where' => [
            ['contact_id', '=', 'user_contact_id'],
          ],
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
    'name' => 'SavedSearch_UserDashboard_Memberships_SearchDisplay_UserDashboard_Memberships',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Memberships',
        'label' => E::ts('Your Membership(s)'),
        'saved_search_id.name' => 'UserDashboard_Memberships',
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
              'key' => 'membership_type_id:label',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'join_date',
              'label' => E::ts('Member Since'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'start_date',
              'label' => E::ts('Start Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'end_date',
              'label' => E::ts('End Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => E::ts('Status'),
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
