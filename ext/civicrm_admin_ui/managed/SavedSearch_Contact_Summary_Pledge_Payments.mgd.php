<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Contact_Summary_Pledge_Payments',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Pledge_Payments',
        'label' => E::ts('Contact Summary Pledge Payments'),
        'api_entity' => 'PledgePayment',
        'api_params' => [
          'version' => 4,
          'select' => [
            'pledge_id',
            'scheduled_amount',
            'scheduled_date',
            'actual_amount',
            'contribution_id.receive_date',
            'reminder_date',
            'reminder_count',
            'status_id:label',
            'contribution_id',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_Contact_Summary_Pledge_Payments_SearchDisplay_Contact_Summary_Pledge_Payments_Subsearch',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contact_Summary_Pledge_Payments_Subsearch',
        'label' => E::ts('Contact Summary Pledge Payments Subsearch'),
        'saved_search_id.name' => 'Contact_Summary_Pledge_Payments',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            ['scheduled_date', 'ASC'],
          ],
          'limit' => 20,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'scheduled_amount',
              'label' => E::ts('Scheduled Amount'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'scheduled_date',
              'label' => E::ts('Scheduled Date'),
              'sortable' => TRUE,
              'format' => 'dateformatshortdate',
            ],
            [
              'type' => 'field',
              'key' => 'actual_amount',
              'label' => E::ts('Paid Amount'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'contribution_id.receive_date',
              'label' => E::ts('Paid Date'),
              'sortable' => TRUE,
              'format' => 'dateformatshortdate',
            ],
            [
              'type' => 'field',
              'key' => 'reminder_date',
              'label' => E::ts('Last Reminder'),
              'sortable' => TRUE,
              'format' => 'dateformatshortdate',
            ],
            [
              'type' => 'field',
              'key' => 'reminder_count',
              'label' => E::ts('Reminders Sent'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'status_id:label',
              'label' => E::ts('Payment Status'),
              'sortable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'PledgePayment',
                  'action' => 'view',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-receipt',
                  'text' => E::ts('View Payment'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'conditions' => [
                    [
                      'contribution_id',
                      'IS NOT EMPTY',
                    ],
                  ],
                ],
                [
                  'path' => 'civicrm/contact/view/contribution?reset=1&action=add&cid=[pledge_id.contact_id]&context=pledge&ppid=[id]',
                  'icon' => 'fa-money-check-dollar',
                  'text' => E::ts('Record Payment'),
                  'style' => 'default',
                  'conditions' => [
                    [
                      'contribution_id',
                      'IS EMPTY',
                    ],
                  ],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'path' => 'civicrm/contact/view/contribution?reset=1&action=add&cid=[pledge_id.contact_id]&context=pledge&ppid=[id]&mode=live',
                  'icon' => 'fa-credit-card',
                  'text' => E::ts('Charge Card'),
                  'style' => 'default',
                  'conditions' => [
                    [
                      'contribution_id',
                      'IS EMPTY',
                    ],
                  ],
                  'task' => '',
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'entity' => 'PledgePayment',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => '',
                  'task' => '',
                  'conditions' => [
                    [
                      'contribution_id',
                      'IS EMPTY',
                    ],
                  ],
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
              'nowrap' => TRUE,
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
          'columnMode' => 'custom',
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
