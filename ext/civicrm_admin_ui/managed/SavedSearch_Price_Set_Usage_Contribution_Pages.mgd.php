<?php
use CRM_CivicrmAdminUi_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_Price_Set_Usage_Contribution_Pages',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Price_Set_Usage_Contribution_Pages',
        'label' => E::ts('Price Set Usage: Contribution Pages'),
        'api_entity' => 'PriceSetEntity',
        'api_params' => [
          'version' => 4,
          'select' => [
            'id',
            'PriceSetEntity_ContributionPage_entity_id_01.title',
            'PriceSetEntity_ContributionPage_entity_id_01.financial_type_id:label',
            'PriceSetEntity_ContributionPage_entity_id_01.start_date',
            'PriceSetEntity_ContributionPage_entity_id_01.end_date',
          ],
          'orderBy' => [],
          'where' => [],
          'groupBy' => [],
          'join' => [
            [
              'ContributionPage AS PriceSetEntity_ContributionPage_entity_id_01',
              'INNER',
              [
                'entity_id',
                '=',
                'PriceSetEntity_ContributionPage_entity_id_01.id',
              ],
              [
                'entity_table',
                '=',
                '\'civicrm_contribution_page\'',
              ],
            ],
          ],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_Price_Set_Usage_Contribution_Pages_SearchDisplay_Price_Set_Usage_Contribution_Pages',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'Price_Set_Usage_Contribution_Pages',
        'label' => E::ts('Price Set Usage: Contribution Pages'),
        'saved_search_id.name' => 'Price_Set_Usage_Contribution_Pages',
        'type' => 'table',
        'settings' => [
          'description' => E::ts(NULL),
          'sort' => [
            [
              'PriceSetEntity_ContributionPage_entity_id_01.title',
              'ASC',
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
              'key' => 'PriceSetEntity_ContributionPage_entity_id_01.title',
              'label' => E::ts('Contribution Page'),
              'sortable' => TRUE,
              'link' => [
                'path' => '',
                'entity' => 'ContributionPage',
                'action' => 'update',
                'join' => 'PriceSetEntity_ContributionPage_entity_id_01',
                'target' => 'crm-popup',
                'task' => '',
              ],
              'title' => E::ts('Update Contribution Page'),
            ],
            [
              'type' => 'field',
              'key' => 'PriceSetEntity_ContributionPage_entity_id_01.financial_type_id:label',
              'label' => E::ts('Financial Type'),
              'sortable' => TRUE,
            ],
            [
              'type' => 'field',
              'key' => 'PriceSetEntity_ContributionPage_entity_id_01.start_date',
              'label' => E::ts('Dates'),
              'sortable' => TRUE,
              'rewrite' => '{if "[PriceSetEntity_ContributionPage_entity_id_01.start_date][PriceSetEntity_ContributionPage_entity_id_01.end_date]"} 
[PriceSetEntity_ContributionPage_entity_id_01.start_date] - [PriceSetEntity_ContributionPage_entity_id_01.end_date]{/if}',
            ],
          ],
          'actions' => FALSE,
          'classes' => ['table', 'table-striped'],
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
