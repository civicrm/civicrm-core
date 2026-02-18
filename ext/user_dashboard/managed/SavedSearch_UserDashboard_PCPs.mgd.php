<?php
use CRM_UserDashboard_ExtensionUtil as E;

if (!CRM_Core_Component::isEnabled('CiviContribute')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_UserDashboard_PCPs',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_PCPs',
        'label' => E::ts('User Dashboard - PCPs'),
        'api_entity' => 'PCP',
        'api_params' => [
          'version' => 4,
          'select' => [
            'title',
            'status_id:label',
            'PCP_ContributionPage_page_id_01.frontend_title',
          ],
          'orderBy' => [],
          'where' => [
            ['contact_id', '=', 'user_contact_id'],
          ],
          'groupBy' => [],
          'join' => [
            [
              'ContributionPage AS PCP_ContributionPage_page_id_01',
              'LEFT',
              [
                'page_id',
                '=',
                'PCP_ContributionPage_page_id_01.id',
              ],
              ['page_type', '=', '\'contribute\''],
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
    'name' => 'SavedSearch_UserDashboard_PCPs_SearchDisplay_UserDashboard_PCPs',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'UserDashboard_PCPs',
        'label' => E::ts('Personal Campaign Pages'),
        'saved_search_id.name' => 'UserDashboard_PCPs',
        'type' => 'table',
        'acl_bypass' => TRUE,
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
              'key' => 'title',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'PCP_ContributionPage_page_id_01.frontend_title',
              'label' => E::ts('Campaign'),
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
