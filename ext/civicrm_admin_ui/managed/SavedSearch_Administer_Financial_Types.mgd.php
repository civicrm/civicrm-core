<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

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
              'dataType' => 'String',
              'label' => E::ts('Name'),
              'sortable' => TRUE,
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
              'key' => 'GROUP_CONCAT_FinancialType_EntityFinancialAccount_FinancialAccount_01_name',
              'dataType' => 'String',
              'label' => E::ts('Financial Accounts'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_deductible',
              'dataType' => 'Boolean',
              'label' => E::ts('Tax-Deductible'),
              'sortable' => TRUE,
              'editable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'is_reserved',
              'dataType' => 'Boolean',
              'label' => E::ts('Reserved'),
              'sortable' => TRUE,
              'editable' => TRUE,
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
              'type' => 'buttons',
              'alignment' => 'text-right',
            ],
          ],
          'addButton' => [
            'path' => 'civicrm/admin/financial/financialType/edit?reset=1&action=add',
            'text' => E::ts('Add Financial Type'),
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
