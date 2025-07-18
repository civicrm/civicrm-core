<?php
use CRM_BatchEntry_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Contribution_Batch_Entry',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
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
      'match' => ['name'],
    ],
  ],
];
