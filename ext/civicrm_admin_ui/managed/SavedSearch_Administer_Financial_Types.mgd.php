<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

// Temporary check can be removed when moving this file to the civi_contribute extension.
if (!CRM_Core_Component::isEnabled('CiviContribute')) {
  return [];
}

return [
  [
    'name' => 'SavedSearch_Administer_Financial_Types',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Administer_Financial_Types',
        'label' => E::ts('Financial Types'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'FinancialType',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'name',
            'description',
            'GROUP_CONCAT(DISTINCT FinancialType_EntityFinancialAccount_FinancialAccount_01.name) AS GROUP_CONCAT_FinancialType_EntityFinancialAccount_FinancialAccount_01_name',
            'is_deductible',
            'is_reserved',
            'is_active',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'id',
          ],
          'join' => [
            [
              'FinancialAccount AS FinancialType_EntityFinancialAccount_FinancialAccount_01',
              'LEFT',
              'EntityFinancialAccount',
              [
                'id',
                '=',
                'FinancialType_EntityFinancialAccount_FinancialAccount_01.entity_id',
              ],
              [
                'FinancialType_EntityFinancialAccount_FinancialAccount_01.entity_table',
                '=',
                "'civicrm_financial_type'",
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
    'name' => 'SavedSearch_Financial_Types_SearchDisplay_Financial_Types_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Financial_Types_Table_1',
        'label' => E::ts('Administer Financial Types'),
        'saved_search_id.name' => 'Administer_Financial_Types',
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
              'name',
              'ASC',
            ],
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'name',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
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
              'key' => 'GROUP_CONCAT_FinancialType_EntityFinancialAccount_FinancialAccount_01_name',
              'label' => E::ts('Financial Accounts'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_deductible',
              'label' => E::ts('Tax-Deductible'),
              'sortable' => TRUE,
              'editable' => TRUE,
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
              'key' => 'is_reserved',
              'label' => E::ts('Reserved'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'path' => 'civicrm/admin/financial/financialType/accounts/list#/?entity_id=[id]',
                  'icon' => 'fa-external-link',
                  'text' => E::ts('Accounts'),
                  'style' => 'default',
                  'condition' => [],
                  'entity' => '',
                  'action' => '',
                  'join' => '',
                  'target' => '',
                ],
              ],
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
            [
              'size' => 'btn-xs',
              'links' => [
                [
                  'icon' => 'fa-pencil',
                  'text' => E::ts('Edit'),
                  'style' => 'default',
                  'condition' => [],
                  'entity' => 'FinancialType',
                  'action' => 'update',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
                [
                  'task' => 'enable',
                  'entity' => 'FinancialType',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-on',
                  'text' => E::ts('Enable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'task' => 'disable',
                  'entity' => 'FinancialType',
                  'target' => 'crm-popup',
                  'icon' => 'fa-toggle-off',
                  'text' => E::ts('Disable'),
                  'style' => 'default',
                  'condition' => [],
                ],
                [
                  'icon' => 'fa-trash',
                  'text' => E::ts('Delete'),
                  'style' => 'danger',
                  'condition' => [],
                  'entity' => 'FinancialType',
                  'action' => 'delete',
                  'join' => '',
                  'target' => 'crm-popup',
                ],
              ],
              'type' => 'menu',
              'icon' => 'fa-bars',
              'alignment' => 'text-right',
            ],
          ],
          'toolbar' => [
            [
              'entity' => 'FinancialType',
              'action' => 'add',
              'target' => 'crm-popup',
              'style' => 'primary',
              'text' => E::ts('Add Financial Type'),
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
