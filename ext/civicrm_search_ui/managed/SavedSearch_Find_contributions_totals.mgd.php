<?php

use CRM_CivicrmSearchUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Find_Contributions_totals',
    'entity' => 'SavedSearch',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Find_Contributions_totals',
        'label' => E::ts('Find Contributions - totals'),
        'form_values' => NULL,
        'mapping_id' => NULL,
        'search_custom_id' => NULL,
        'api_entity' => 'Contribution',
        'api_params' => [
          'version' => 4,
          'select' => [
            'GROUP_CONCAT(DISTINCT Contribution_Contact_contact_id_01.display_name) AS GROUP_CONCAT_Contribution_Contact_contact_id_01_display_name',
            'SUM(total_amount) AS SUM_total_amount',
            'GROUP_CONCAT(DISTINCT financial_type_id:label) AS GROUP_CONCAT_financial_type_id_label',
            'GROUP_CONCAT(DISTINCT source) AS GROUP_CONCAT_source',
            'GROUP_CONCAT(DISTINCT receive_date) AS GROUP_CONCAT_receive_date',
            'GROUP_CONCAT(DISTINCT thankyou_date) AS GROUP_CONCAT_thankyou_date',
            'GROUP_CONCAT(DISTINCT contribution_status_id:label) AS GROUP_CONCAT_contribution_status_id_label',
            'GROUP_CONCAT(DISTINCT receipt_date) AS GROUP_CONCAT_receipt_date',
            'GROUP_CONCAT(DISTINCT contribution_recur_id) AS GROUP_CONCAT_contribution_recur_id',
            'COUNT(id) AS COUNT_id',
            'currency:label',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [
            'currency',
          ],
          'join' => [
            [
              'Contact AS Contribution_Contact_contact_id_01',
              'INNER',
              [
                'contact_id',
                '=',
                'Contribution_Contact_contact_id_01.id',
              ],
            ],
            [
              'ContributionSoft AS Contribution_ContributionSoft_contribution_id_01',
              'LEFT',
              [
                'id',
                '=',
                'Contribution_ContributionSoft_contribution_id_01.contribution_id',
              ],
            ],
            [
              'PCP AS Contribution_ContributionSoft_contribution_id_01_ContributionSoft_PCP_pcp_id_01',
              'LEFT',
              [
                'Contribution_ContributionSoft_contribution_id_01.pcp_id',
                '=',
                'Contribution_ContributionSoft_contribution_id_01_ContributionSoft_PCP_pcp_id_01.id',
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
    'name' => 'SavedSearch_Find_Contributions_totals_SearchDisplay_Find_Contributions_totals_Table_1',
    'entity' => 'SearchDisplay',
    'cleanup' => 'always',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Find_Contributions_totals_Table_1',
        'label' => E::ts('Find Contributions - totals Table 1'),
        'saved_search_id.name' => 'Find_Contributions_totals',
        'type' => 'table',
        'settings' => [
          'description' => NULL,
          'sort' => [
            [
              'SUM_total_amount',
              'DESC',
            ],
          ],
          'limit' => 50,
          'pager' => [
            'hide_single' => TRUE,
          ],
          'placeholder' => 5,
          'columns' => [
            [
              'type' => 'field',
              'key' => 'currency:label',
              'label' => E::ts('Currency'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'SUM_total_amount',
              'label' => E::ts('Total Amount'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'COUNT_id',
              'label' => E::ts('Number of contributions'),
              'sortable' => TRUE,
            ],
          ],
          'actions' => FALSE,
          'classes' => [
            'table',
            'table-striped',
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
