<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_contribute extension.
if (!CRM_Core_Component::isEnabled('CiviContribute')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_Administer_Payment_Processors',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Payment_Processors',
        'label' => E::ts('Administer Payment Processors'),
        'api_entity' => 'PaymentProcessor',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'title',
            'description',
            'payment_processor_type_id:label',
            'is_active',
            'is_default',
            'PaymentProcessor_EntityFinancialAccount_FinancialAccount_01.name',
          ],
          'orderBy' => [],
          'where' => [
            [
              'is_test',
              '=',
              FALSE,
            ],
            [
              'domain_id:name',
              '=',
              'current_domain',
            ],
          ],
          'groupBy' => [],
          'join' => [
            [
              'FinancialAccount AS PaymentProcessor_EntityFinancialAccount_FinancialAccount_01',
              'LEFT',
              'EntityFinancialAccount',
              [
                'id',
                '=',
                'PaymentProcessor_EntityFinancialAccount_FinancialAccount_01.entity_id',
              ],
              [
                'PaymentProcessor_EntityFinancialAccount_FinancialAccount_01.entity_table',
                '=',
                "'civicrm_payment_processor'",
              ],
            ],
          ],
          'having' => [],
        ],
        'expires_date' => NULL,
        'description' => NULL,
      ],
      'match' => [
        'name',
      ],
    ],
  ],
  [
    'name' => 'SavedSearch_Administer_Payment_Processors_SearchDisplay_Administer_Payment_Processors_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Payment_Processors_Table',
        'label' => E::ts('Administer Payment Processors'),
        'saved_search_id.name' => 'Administer_Payment_Processors',
        'type' => 'table',
        'settings' => [
          'actions' => TRUE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
            'crm-sticky-header',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'sort' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'label' => E::ts('ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'name',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'title',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'payment_processor_type_id:label',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'PaymentProcessor_EntityFinancialAccount_FinancialAccount_01.name',
              'label' => E::ts('Financial Account'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'label' => E::ts('Enabled'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_default',
              'label' => E::ts('Default'),
              'sortable' => TRUE,
              'rewrite' => '[none]',
              'icons' => [
                [
                  'icon' => 'fa-check',
                  'side' => 'left',
                  'if' => ['is_default', '=', TRUE],
                ],
              ],
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'entity' => 'PaymentProcessor',
                  'action' => 'update',
                  'join' => '',
                  // NOTE: CiviConnect behaviors currently incompatbile with loading manage form in AJAX popup
                  // 'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
                [
                  'task' => 'enable',
                  'entity' => 'PaymentProcessor',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-on',
                  'text' => E::ts('Enable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'task' => 'disable',
                  'entity' => 'PaymentProcessor',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-off',
                  'text' => E::ts('Disable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'entity' => 'PaymentProcessor',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'path' => '',
                  'condition' => [],
                ],
              ],
              'type' => 'menu',
              'icon' => 'fa-bars',
              'alignment' => 'text-right',
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'PaymentProcessor',
              'action' => 'add',
              // NOTE: CiviConnect behaviors currently incompatbile with loading manage form in AJAX popup
              // 'target' => 'crm-popup',
              'style' => 'primary',
              'text' => E::ts('Add Payment Processor'),
              'icon' => 'fa-plus',
            ],
          ],
          'cssRules' => [
            [
              'disabled',
              'is_active',
              '=',
              FALSE,
            ],
          ],
        ],
        'acl_bypass' => FALSE,
      ],
      'match' => [
        'name',
        'saved_search_id',
      ],
    ],
  ],
];
