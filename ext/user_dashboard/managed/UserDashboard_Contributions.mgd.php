<?php
use CRM_UserDashboard_ExtensionUtil as E;

if (!CRM_Core_Component::isEnabled('CiviContribute')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_UserDashboard_Contributions',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Contributions',
        'label' => E::ts('User Dashboard - Contributions'),
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => [
            'total_amount',
            'financial_type_id:label',
            'contribution_status_id:label',
            'receive_date',
            'receipt_date',
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
    'name' => 'SavedSearch_UserDashboard_Contributions_SearchDisplay_UserDashboard_Contributions',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Contributions',
        'label' => E::ts('Your Contribution(s)'),
        'saved_search_id.name' => 'UserDashboard_Contributions',
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
              'key' => 'total_amount',
              'dataType' => 'String',
              'label' => E::ts('Total Amount'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'financial_type_id:label',
              'dataType' => 'String',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'receive_date',
              'dataType' => 'Date',
              'label' => E::ts('Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'receipt_date',
              'dataType' => 'Date',
              'label' => E::ts('Receipt Sent'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contribution_status_id:label',
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
