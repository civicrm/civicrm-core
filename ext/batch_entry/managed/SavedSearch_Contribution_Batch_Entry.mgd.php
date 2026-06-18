<?php
use CRM_BatchEntry_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Contribution_Batch_Entry',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contribution_Batch_Entry',
        'label' => E::ts('Contribution Batch'),
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => [
            'contact_id',
            'total_amount',
            'fee_amount',
            'net_amount',
            'contribution_status_id:label',
            'financial_type_id:label',
            'receive_date',
            'source',
            'payment_instrument_id:label',
            'check_number',
            'invoice_number',
            'Contribution_ContributionSoft_contribution_id_01.soft_credit_type_id:label',
            'Contribution_ContributionSoft_contribution_id_01.contact_id',
            'Contribution_ContributionSoft_contribution_id_01.amount',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'ContributionSoft AS Contribution_ContributionSoft_contribution_id_01',
              'LEFT',
              [
                'id',
                '=',
                'Contribution_ContributionSoft_contribution_id_01.contribution_id',
              ],
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
    'name' => 'SavedSearch_Contribution_Batch_Entry_SearchDisplay_Contribution_Batch',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Contribution_Batch',
        'label' => E::ts('Contribution Batch'),
        'saved_search_id.name' => 'Contribution_Batch_Entry',
        'type' => 'batch',
        'settings' => [
          'classes' => [
            'table',
            'table-striped',
            'table-bordered',
            'crm-sticky-header',
          ],
          'limit' => 15,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'columns' => [
            [
              'type' => 'field',
              'key' => 'contact_id',
              'label' => 'Contact',
              'required' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'total_amount',
              'label' => 'Total Amount',
              'tally' => [
                'fn' => 'SUM',
                'target' => TRUE,
              ],
              'required' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'fee_amount',
              'label' => 'Fee Amount',
              'tally' => [
                'fn' => 'SUM',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'net_amount',
              'label' => 'Net Amount',
              'tally' => [
                'fn' => 'SUM',
              ],
            ],
            [
              'type' => 'field',
              'key' => 'contribution_status_id:label',
              'label' => 'Contribution Status',
              'default' => '1',
            ],
            [
              'type' => 'field',
              'key' => 'financial_type_id:label',
              'label' => 'Financial Type',
              'required' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'receive_date',
              'label' => 'Contribution Date',
            ],
            [
              'type' => 'field',
              'key' => 'source',
              'label' => 'Contribution Source',
            ],
            [
              'type' => 'field',
              'key' => 'payment_instrument_id:label',
              'label' => 'Payment Method',
            ],
            [
              'type' => 'field',
              'key' => 'check_number',
              'label' => 'Check Number',
            ],
            [
              'type' => 'field',
              'key' => 'invoice_number',
              'label' => 'Invoice Number',
            ],
            [
              'type' => 'field',
              'key' => 'Contribution_ContributionSoft_contribution_id_01.soft_credit_type_id:label',
              'label' => 'Soft Credit Type',
            ],
            [
              'type' => 'field',
              'key' => 'Contribution_ContributionSoft_contribution_id_01.contact_id',
              'label' => 'Soft Credit Contact',
            ],
          ],
          'tally' => [],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
