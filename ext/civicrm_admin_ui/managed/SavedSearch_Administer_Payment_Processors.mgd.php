<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

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
          'actions' => FALSE,
          'limit' => 50,
          'classes' => [
            'table',
            'table-striped',
          ],
          'pager' => [
            'show_count' => TRUE,
            'expose_limit' => TRUE,
          ],
          'placeholder' => 5,
          'sort' => [],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'id',
              'dataType' => 'Integer',
              'label' => E::ts('ID'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'name',
              'dataType' => 'String',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'title',
              'dataType' => 'String',
              'label' => E::ts('Title'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'dataType' => 'String',
              'label' => E::ts('Description'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'payment_processor_type_id:label',
              'dataType' => 'Integer',
              'label' => E::ts('Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'PaymentProcessor_EntityFinancialAccount_FinancialAccount_01.name',
              'dataType' => 'String',
              'label' => E::ts('Financial Account'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'dataType' => 'Boolean',
              'label' => E::ts('Enabled'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_default',
              'dataType' => 'Boolean',
              'label' => E::ts('Default'),
              'sortable' => TRUE,
              'rewrite' => ' ',
              'icons' => [
                [
                  'icon' => 'fa-check-square-o',
                  'side' => 'left',
                  'if' => [
                    'is_default',
                    '=',
                    TRUE,
                  ],
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
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'path' => '',
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
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
          'addButton' => [
            'path' => 'civicrm/admin/paymentProcessor/edit?action=add&reset=1',
            'text' => E::ts('Add Payment Processor'),
            'icon' => 'fa-plus',
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
    ],
  ],
];
