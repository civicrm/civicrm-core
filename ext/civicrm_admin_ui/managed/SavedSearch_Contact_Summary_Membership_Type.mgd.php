<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_member extension.
if (!CRM_Core_Component::isEnabled('CiviMember')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_Contact_Summary_Membership_Type',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Membership_Type',
        'label' => E::ts('Contact Summary Membership Type'),
        'api_entity' => 'MembershipType',
        'api_params' => [
          'version' => 4,
          'select' => [
            'name',
            'period_type:label',
            'financial_type_id:label',
            'fixed_period_start_day',
            'minimum_fee',
            'duration_interval',
            'visibility:label',
          ],
          'orderBy' => [],
          'where' => [
            [
              'is_active',
              '=',
              TRUE,
            ],
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
    'name' => 'SavedSearch_Contact_Summary_Membership_Type_SearchDisplay_Contact_Summary_Membership_Type',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Membership_Type',
        'label' => E::ts('Contact Summary Membership Type'),
        'saved_search_id.name' => 'Contact_Summary_Membership_Type',
        'type' => 'table',
        'settings' => [
          'description' => E::ts('The following Membership Types are associated with this organization. Click Members for a listing of all contacts who have memberships of that type. Click Edit to modify the settings for that type.'),
          'sort' => [],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'period_type:label',
              'label' => E::ts('Period'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'fixed_period_start_day',
              'label' => E::ts('Fixed Start'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'minimum_fee',
              'label' => E::ts('Minimum Fee'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'duration_interval',
              'label' => E::ts('Duration'),
              'sortable' => TRUE,
              'rewrite' => '[duration_interval] [duration_unit:label]',
            ],
            [
              'type' => 'field',
              'key' => 'visibility:label',
              'label' => E::ts('Visibility'),
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'path' => 'civicrm/member/search?reset=1&force=1&type=[id]',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('Members'),
                  'style' => 'default',
                  'condition' => [],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'entity' => 'MembershipType',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
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
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
