<?php
use CRM_UserDashboard_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_UserDashboard_Groups',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Groups',
        'label' => E::ts('User Dashboard - Groups'),
        'api_entity' => 'Group',
        'api_params' => [
          'version' => 4,
          'select' => [
            'frontend_title',
            'Group_GroupContact_Contact_01.status:label',
            'MAX(Group_SubscriptionHistory_group_id_01.date) AS MAX_Group_SubscriptionHistory_group_id_01_date',
          ],
          'orderBy' => [],
          'where' => [
            [
              'Group_GroupContact_Contact_01.id',
              '=',
              'user_contact_id',
            ],
            [
              'visibility:name',
              '=',
              'Public Pages',
            ],
            [
              'is_active',
              '=',
              TRUE,
            ],
          ],
          'groupBy' => [
            'id',
            'Group_GroupContact_Contact_01.id',
          ],
          'join' => [
            [
              'Contact AS Group_GroupContact_Contact_01',
              'LEFT',
              'GroupContact',
              [
                'id',
                '=',
                'Group_GroupContact_Contact_01.group_id',
              ],
            ],
            [
              'SubscriptionHistory AS Group_SubscriptionHistory_group_id_01',
              'LEFT',
              [
                'id',
                '=',
                'Group_SubscriptionHistory_group_id_01.group_id',
              ],
              [
                'Group_SubscriptionHistory_group_id_01.contact_id',
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
    'name' => 'SavedSearch_UserDashboard_Groups_SearchDisplay_UserDashboard_Groups',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Groups',
        'label' => E::ts('Your Group(s)'),
        'saved_search_id.name' => 'UserDashboard_Groups',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'Group_GroupContact_Contact_01.status',
              'ASC',
            ],
          ],
          'limit' => 20,
          'pager' => [
            'expose_limit' => TRUE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 1,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'frontend_title',
              'dataType' => 'String',
              'label' => E::ts('Group'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'Group_GroupContact_Contact_01.status:label',
              'dataType' => 'String',
              'label' => E::ts('Status'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'MAX_Group_SubscriptionHistory_group_id_01_date',
              'dataType' => 'Timestamp',
              'label' => E::ts('Since'),
              'sortable' => TRUE,
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
          ],
          'cssRules' => [
            [
              'disabled',
              'Group_GroupContact_Contact_01.status',
              '=',
              'Removed',
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
];
