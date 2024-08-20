<?php
use CRM_UserDashboard_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_UserDashboard_Activities',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Activities',
        'label' => E::ts('User Dashboard - Activities'),
        'api_entity' => 'Activity',
        'api_params' => [
          'version' => 4,
          'select' => [
            'subject',
            'activity_type_id:label',
            'GROUP_CONCAT(DISTINCT Activity_ActivityContact_Contact_01.sort_name) AS GROUP_CONCAT_Activity_ActivityContact_Contact_01_sort_name',
            'GROUP_CONCAT(DISTINCT Activity_ActivityContact_Contact_02.sort_name) AS GROUP_CONCAT_Activity_ActivityContact_Contact_02_sort_name',
            'activity_date_time',
            'status_id:label',
          ],
          'orderBy' => [],
          'where' => [
            [
              'status_id:name',
              '!=',
              'Completed',
            ],
          ],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'Contact AS Activity_ActivityContact_Contact_01',
              'LEFT',
              'ActivityContact',
              [
                'id',
                '=',
                'Activity_ActivityContact_Contact_01.activity_id',
              ],
              [
                'Activity_ActivityContact_Contact_01.record_type_id:name',
                '=',
                '"Activity Source"',
              ],
            ],
            [
              'Contact AS Activity_ActivityContact_Contact_02',
              'LEFT',
              'ActivityContact',
              [
                'id',
                '=',
                'Activity_ActivityContact_Contact_02.activity_id',
              ],
              [
                'Activity_ActivityContact_Contact_02.record_type_id:name',
                '=',
                '"Activity Targets"',
              ],
            ],
            [
              'Contact AS Activity_ActivityContact_Contact_03',
              'INNER',
              'ActivityContact',
              [
                'id',
                '=',
                'Activity_ActivityContact_Contact_03.activity_id',
              ],
              [
                'Activity_ActivityContact_Contact_03.record_type_id:name',
                '=',
                '"Activity Assignees"',
              ],
              [
                'Activity_ActivityContact_Contact_03.id',
                '=',
                '"user_contact_id"',
              ],
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
    'name' => 'SavedSearch_UserDashboard_Activities_SearchDisplay_UserDashboard_Activities',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Activities',
        'label' => E::ts('Your Assigned Activities'),
        'saved_search_id.name' => 'UserDashboard_Activities',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [],
          'limit' => 20,
          'pager' => [
            'expose_limit' => TRUE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 1,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'activity_type_id:label',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'subject',
              'label' => E::ts('Subject'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Activity_ActivityContact_Contact_01_sort_name',
              'label' => E::ts('Added by'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'GROUP_CONCAT_Activity_ActivityContact_Contact_02_sort_name',
              'label' => E::ts('With'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'activity_date_time',
              'label' => E::ts('Date'),
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
