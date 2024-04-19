<?php

return [
  'name' => 'CaseActivity',
  'table' => 'civicrm_case_activity',
  'class' => 'CRM_Case_DAO_CaseActivity',
  'getInfo' => fn() => [
    'title' => ts('Case Activity'),
    'title_plural' => ts('Case Activities'),
    'description' => ts('Joining table for case-activity associations.'),
    'log' => TRUE,
    'add' => '1.8',
  ],
  'getIndices' => fn() => [
    'UI_case_activity_id' => [
      'fields' => [
        'case_id' => TRUE,
        'activity_id' => TRUE,
      ],
      'add' => '2.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Case Activity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique case-activity association id'),
      'add' => '1.8',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'case_id' => [
      'title' => ts('Case ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Case ID of case-activity association.'),
      'add' => '1.8',
      'input_attrs' => [
        'label' => ts('Case'),
      ],
      'entity_reference' => [
        'entity' => 'Case',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'activity_id' => [
      'title' => ts('Activity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Activity ID of case-activity association.'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Activity'),
      ],
      'entity_reference' => [
        'entity' => 'Activity',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
