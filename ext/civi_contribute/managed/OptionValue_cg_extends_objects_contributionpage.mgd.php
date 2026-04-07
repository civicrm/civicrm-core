<?php
use CRM_Contribute_ExtensionUtil as E;

// This enables custom fields for ContributionPage entities
return [
  [
    'name' => 'cg_extend_objects:ContributionPage',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'cg_extend_objects',
        'label' => E::ts('Contribution Page'),
        'value' => 'ContributionPage',
        'name' => 'civicrm_contribution_page',
        'is_reserved' => TRUE,
        'is_active' => TRUE,
        'grouping' => 'financial_type_id',
      ],
      'match' => [
        'name',
        'option_group_id',
      ],
    ],
  ],
];
