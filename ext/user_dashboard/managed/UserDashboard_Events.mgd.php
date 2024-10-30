<?php
use CRM_UserDashboard_ExtensionUtil as E;

if (!CRM_Core_Component::isEnabled('CiviEvent')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_UserDashboard_Events',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Events',
        'label' => E::ts('User Dashboard - Events'),
        'api_entity' => 'Participant',
        'api_params' => [
          'version' => 4,
          'select' => [
            'event_id.title',
            'role_id:label',
            'status_id:label',
            'event_id.start_date',
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
    'name' => 'SavedSearch_UserDashboard_Events_SearchDisplay_UserDashboard_Events',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Events',
        'label' => E::ts('Your Event(s)'),
        'saved_search_id.name' => 'UserDashboard_Events',
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
              'key' => 'event_id.title',
              'dataType' => 'String',
              'label' => E::ts('Event'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'event_id.start_date',
              'dataType' => 'Date',
              'label' => E::ts('Event Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'role_id:label',
              'dataType' => 'String',
              'label' => E::ts('Role'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'dataType' => 'String',
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
