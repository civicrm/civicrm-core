<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_contribute extension.
if (!CRM_Core_Component::isEnabled('CiviContribute')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_Administer_Financial_Accounts',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Financial_Accounts',
        'label' => E::ts('Administer Financial Accounts'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'FinancialAccount',
        'api_params' => [
          'version' => 4,
          'select' => [
            'name',
            'description',
            'accounting_code',
            'financial_account_type_id:label',
            'account_type_code',
            'is_deductible',
            'is_reserved',
            'is_default',
            'is_active',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [],
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
    'name' => 'SavedSearch_Administer_Financial_Accounts_SearchDisplay_Administer_Financial_Accounts_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Financial_Accounts_Table_1',
        'label' => E::ts('Financial Accounts Table'),
        'saved_search_id.name' => 'Administer_Financial_Accounts',
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
          'sort' => [
            [
              'financial_account_type_id:label',
              'ASC',
            ],
            [
              'account_type_code',
              'ASC',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'label' => 'Name',
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'description',
              'label' => 'Description',
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'accounting_code',
              'label' => 'Acctg Code',
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'financial_account_type_id:label',
              'label' => 'Account Type',
              'sortable' => TRUE,
              'rewrite' => '[financial_account_type_id:label] ([account_type_code])',
            ],
            [
              'type' => 'field',
              'key' => 'is_deductible',
              'label' => 'Deductible',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_active',
              'label' => 'Enabled',
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_reserved',
              'label' => 'Reserved',
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_default',
              'label' => 'Default',
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
                  'entity' => 'FinancialAccount',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-pencil',
                  'text' => 'Edit',
                  'style' => 'default',
                  'path' => '',
                  'condition' => [],
                ],
                [
                  'task' => 'enable',
                  'entity' => 'FinancialAccount',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-on',
                  'text' => 'Enable',
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'task' => 'disable',
                  'entity' => 'FinancialAccount',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-off',
                  'text' => 'Disable',
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'entity' => 'FinancialAccount',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                  'icon' => 'fa-trash',
                  'text' => 'Delete',
                  'style' => 'danger',
                  'path' => '',
                  'condition' => ['is_reserved', '=', FALSE],
                ],
              ],
              'type' => 'menu',
              'icon' => 'fa-bars',
              'alignment' => 'text-right',
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'FinancialAccount',
              'action' => 'add',
              'target' => 'crm-popup',
              'style' => 'primary',
              'text' => 'Add Financial Account',
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
