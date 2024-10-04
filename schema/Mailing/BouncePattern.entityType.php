<?php

return [
  'name' => 'BouncePattern',
  'table' => 'civicrm_mailing_bounce_pattern',
  'class' => 'CRM_Mailing_DAO_BouncePattern',
  'getInfo' => fn() => [
    'title' => ts('Bounce Pattern'),
    'title_plural' => ts('Bounce Patterns'),
    'description' => ts('Pseudo-constant table of patterns for bounce classification'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Bounce Pattern ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'bounce_type_id' => [
      'title' => ts('Bounce Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Type of bounce'),
      'input_attrs' => [
        'label' => ts('Bounce Type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_mailing_bounce_type',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'BounceType',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'pattern' => [
      'title' => ts('Pattern'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('A regexp to match a message to a bounce type'),
    ],
  ],
];
