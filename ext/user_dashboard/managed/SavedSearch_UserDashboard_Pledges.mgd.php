<?php
use CRM_UserDashboard_ExtensionUtil as E;

if (!CRM_Core_Component::isEnabled('CiviPledge')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_UserDashboard_Pledges',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Pledges',
        'label' => E::ts('User Dashboard - Pledges'),
        'api_entity' => 'Pledge',
        'api_params' => [
          'version' => 4,
          'select' => [
            'amount',
            'SUM(Pledge_PledgePayment_pledge_id_01.actual_amount) AS SUM_Pledge_PledgePayment_pledge_id_01_actual_amount',
            'financial_type_id:label',
            'create_date',
            'MIN(Pledge_PledgePayment_pledge_id_02.scheduled_date) AS MIN_Pledge_PledgePayment_pledge_id_02_scheduled_date',
            'MAX(Pledge_PledgePayment_pledge_id_02.scheduled_amount) AS MAX_Pledge_PledgePayment_pledge_id_02_scheduled_amount',
            'status_id:label',
          ],
          'orderBy' => [],
          'where' => [
            ['contact_id', '=', 'user_contact_id'],
          ],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'PledgePayment AS Pledge_PledgePayment_pledge_id_01',
              'LEFT',
              ['id', '=', 'Pledge_PledgePayment_pledge_id_01.pledge_id'],
              ['Pledge_PledgePayment_pledge_id_01.status_id:name', '=', '"Completed"'],
            ],
            [
              'PledgePayment AS Pledge_PledgePayment_pledge_id_02',
              'LEFT',
              ['id', '=', 'Pledge_PledgePayment_pledge_id_02.pledge_id'],
              ['Pledge_PledgePayment_pledge_id_02.status_id:name', '!=', '"Completed"'],
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
    'name' => 'SavedSearch_UserDashboard_Pledges_SearchDisplay_UserDashboard_Pledges',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_Pledges',
        'label' => E::ts('Your Pledges'),
        'saved_search_id.name' => 'UserDashboard_Pledges',
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
              'key' => 'amount',
              'label' => E::ts('Pledged'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'SUM_Pledge_PledgePayment_pledge_id_01_actual_amount',
              'label' => E::ts('Total Paid'),
              'sortable' => TRUE,
              'empty_value' => '0',
            ],
            [
              'type' => 'field',
              'key' => 'financial_type_id:label',
              'label' => E::ts('Pledged For'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'create_date',
              'label' => E::ts('Pledge Made'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'MIN_Pledge_PledgePayment_pledge_id_02_scheduled_date',
              'label' => E::ts('Next Pay Date'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'MAX_Pledge_PledgePayment_pledge_id_02_scheduled_amount',
              'label' => E::ts('Next Amount'),
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
