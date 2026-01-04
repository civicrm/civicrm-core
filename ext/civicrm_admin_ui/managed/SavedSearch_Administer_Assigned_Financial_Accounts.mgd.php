<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_contribute extension.
if (!CRM_Core_Component::isEnabled('CiviContribute')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_Administer_Assigned_Financial_Accounts',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Assigned_Financial_Accounts',
        'label' => E::ts('Administer Assigned Financial Accounts'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'EntityFinancialAccount',
        'api_params' => [
          'version' => 4,
          'select' => [
            'account_relationship:label',
            'financial_account_id:label',
            'EntityFinancialAccount_FinancialAccount_financial_account_id_01.accounting_code',
            'EntityFinancialAccount_FinancialAccount_financial_account_id_01.financial_account_type_id:label',
            'EntityFinancialAccount_FinancialAccount_financial_account_id_01.account_type_code',
            'EntityFinancialAccount_FinancialAccount_financial_account_id_01.contact_id.display_name',
            'EntityFinancialAccount_FinancialAccount_financial_account_id_01.is_active',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'FinancialAccount AS EntityFinancialAccount_FinancialAccount_financial_account_id_01',
              'LEFT',
              [
                'financial_account_id',
                '=',
                'EntityFinancialAccount_FinancialAccount_financial_account_id_01.id',
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
    'name' => 'SavedSearch_Administer_Assigned_Financial_Accounts_SearchDisplay_Entity_Financial_Accounts_Table',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Entity_Financial_Accounts_Table_1',
        'label' => E::ts('Entity Financial Accounts Table'),
        'saved_search_id.name' => 'Administer_Assigned_Financial_Accounts',
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
              'key' => 'account_relationship:label',
              'label' => E::ts('Relationship'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'financial_account_id:label',
              'label' => E::ts('Financial Account'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'EntityFinancialAccount_FinancialAccount_financial_account_id_01.accounting_code',
              'label' => E::ts('Accounting Code'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'EntityFinancialAccount_FinancialAccount_financial_account_id_01.financial_account_type_id:label',
              'label' => E::ts('Account Type (Code)'),
              'sortable' => TRUE,
              'rewrite' => '[EntityFinancialAccount_FinancialAccount_financial_account_id_01.financial_account_type_id:label] ([EntityFinancialAccount_FinancialAccount_financial_account_id_01.account_type_code])',
            ],
            [
              'type' => 'field',
              'key' => 'EntityFinancialAccount_FinancialAccount_financial_account_id_01.contact_id.display_name',
              'label' => E::ts('Owner'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'EntityFinancialAccount_FinancialAccount_financial_account_id_01.is_active',
              'label' => E::ts('Enabled'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'path' => 'civicrm/admin/financial/financialType/accounts?action=update&id=[id]&aid=[entity_id]&reset=1',
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'path' => 'civicrm/admin/financial/financialType/accounts?action=delete&id=[id]&aid=[entity_id]&reset=1',
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'condition' => [
                    'account_relationship:name',
                    '!=',
                    'Accounts Receivable Account is',
                  ],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
              ],
              'type' => 'menu',
              'icon' => 'fa-bars',
              'alignment' => 'text-right',
            ],
          ],
          'cssRules' => [
            [
              'disabled',
              'EntityFinancialAccount_FinancialAccount_financial_account_id_01.is_active',
              '=',
              FALSE,
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'EntityFinancialAccount',
              'action' => 'add',
              'target' => 'crm-popup',
              'style' => 'primary',
              'text' => E::ts('Add Assigned Account'),
              'icon' => 'fa-plus',
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
