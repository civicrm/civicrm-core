<?php

return [
  'name' => 'DedupeRule',
  'table' => 'civicrm_dedupe_rule',
  'class' => 'CRM_Dedupe_DAO_DedupeRule',
  'getInfo' => fn() => [
    'title' => ts('Dedupe Rule'),
    'title_plural' => ts('Dedupe Rules'),
    'description' => ts('Dedupe rules for use by rule groups'),
    'add' => '1.8',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Dedupe Rule ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique dedupe rule id'),
      'add' => '1.8',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'dedupe_rule_group_id' => [
      'title' => ts('Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('The id of the rule group this rule belongs to'),
      'add' => '1.8',
      'input_attrs' => [
        'label' => ts('Group'),
      ],
      'entity_reference' => [
        'entity' => 'DedupeRuleGroup',
        'key' => 'id',
      ],
    ],
    'rule_table' => [
      'title' => ts('Rule Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('The name of the table this rule is about'),
      'add' => '1.8',
    ],
    'rule_field' => [
      'title' => ts('Rule Field'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('The name of the field of the table referenced in rule_table'),
      'add' => '1.8',
    ],
    'rule_length' => [
      'title' => ts('Rule Length'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Text',
      'description' => ts('The length of the matching substring'),
      'add' => '1.8',
    ],
    'rule_weight' => [
      'title' => ts('Order'),
      'sql_type' => 'int',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('The weight of the rule'),
      'add' => '1.8',
    ],
  ],
];
