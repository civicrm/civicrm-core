<?php
use CRM_ChartKit_ExtensionUtil as E;

return [
  [
    'name' => 'SearchDisplayType_ChartKit',
    'entity' => 'OptionValue',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'search_display_type',
        'value' => 'chart-kit',
        'name' => 'crm-search-display-chart-kit',
        'label' => E::ts('Chart'),
        'description' => E::ts('Visualize data by choosing a chart type.'),
        'icon' => 'fa-pie-chart',
        'is_reserved' => TRUE,
      ],
      'match' => ['option_group_id', 'name'],
    ],
  ],
];
