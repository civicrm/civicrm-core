<?php
use CRM_Grant_ExtensionUtil as E;

return [
  [
    'name' => 'SavedSearch_CiviGrant_Dashboard_Graph',
    'entity' => 'SavedSearch',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'CiviGrant_Dashboard_Graph',
        'label' => E::ts('CiviGrant Dashboard Graph'),
        'api_entity' => 'Grant',
        'api_params' => [
          'version' => 4,
          'select' => [
            'SUM(amount_granted) AS SUM_amount_granted',
            'YEAR(money_transfer_date) AS YEAR_money_transfer_date',
          ],
          'orderBy' => ['money_transfer_date' => 'ASC'],
          'where' => [
            [
              'YEAR(money_transfer_date)',
              '>=',
              'now - 4 year',
            ],
          ],
          'groupBy' => [
            'YEAR(money_transfer_date)',
          ],
          'join' => [],
          'having' => [],
        ],
      ],
      'match' => ['name'],
    ],
  ],
  [
    'name' => 'SavedSearch_CiviGrant_Dashboard_Graph_SearchDisplay_CiviGrant_Dashboard_Graph',
    'entity' => 'SearchDisplay',
    'cleanup' => 'unused',
    'update' => 'unmodified',
    'params' => [
      'version' => 4,
      'values' => [
        'name' => 'CiviGrant_Dashboard_Graph',
        'label' => E::ts('CiviGrant Dashboard Graph'),
        'saved_search_id.name' => 'CiviGrant_Dashboard_Graph',
        'type' => 'chart-kit',
        'settings' => [
          'columns' => [
            [
              'axis' => 'x',
              'key' => 'YEAR_money_transfer_date',
              'index' => 0,
              'name' => 'x_0',
              'label' => E::ts('Year'),
              'sourceDataType' => 'Integer',
              'scaleType' => 'categorical',
              'datePrecision' => NULL,
              'reduceType' => 'list',
              'seriesType' => NULL,
              'dataLabelType' => 'none',
              'dataLabelFormatter' => 'none',
            ],
            [
              'axis' => 'y',
              'key' => 'SUM_amount_granted',
              'index' => 1,
              'name' => 'y_0',
              'label' => E::ts('Amount granted'),
              'sourceDataType' => 'Money',
              'scaleType' => 'numeric',
              'datePrecision' => NULL,
              'reduceType' => 'sum',
              'seriesType' => NULL,
              'dataLabelType' => 'none',
              'dataLabelFormatter' => 'formatMoney',
            ],
          ],
          'format' => [
            'labelColor' => '#000000',
            'backgroundColor' => '#f2f2ed',
            'height' => 300,
            'width' => 700,
            'padding' => [
              'outer' => 10,
              'clip' => 20,
              'top' => 50,
              'bottom' => 50,
              'left' => 80,
              'right' => 50,
              'inner' => 0,
            ],
          ],
          'chartType' => 'bar',
        ],
      ],
      'match' => [
        'saved_search_id',
        'name',
      ],
    ],
  ],
];
