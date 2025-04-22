<?php

return [
  'name' => 'MailingAB',
  'table' => 'civicrm_mailing_abtest',
  'class' => 'CRM_Mailing_DAO_MailingAB',
  'getInfo' => fn() => [
    'title' => ts('Mailing AB'),
    'title_plural' => ts('Mailing ABs'),
    'description' => ts('Stores information about abtesting'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('MailingAB ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Name of the A/B test'),
      'add' => '4.6',
    ],
    'status' => [
      'title' => ts('Status'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Select',
      'description' => ts('Status'),
      'add' => '4.6',
      'pseudoconstant' => [
        'callback' => ['CRM_Mailing_PseudoConstant', 'abStatus'],
      ],
    ],
    'mailing_id_a' => [
      'title' => ts('Mailing ID (A)'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('The first experimental mailing ("A" condition)'),
      'add' => '4.6',
    ],
    'mailing_id_b' => [
      'title' => ts('Mailing ID (B)'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('The second experimental mailing ("B" condition)'),
      'add' => '4.6',
    ],
    'mailing_id_c' => [
      'title' => ts('Mailing ID (C)'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('The final, general mailing (derived from A or B)'),
      'add' => '4.6',
    ],
    'domain_id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Which site is this mailing for'),
      'add' => '4.6',
    ],
    'testing_criteria' => [
      'title' => ts('Testing Criteria'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Select',
      'add' => '4.6',
      'pseudoconstant' => [
        'callback' => ['CRM_Mailing_PseudoConstant', 'abTestCriteria'],
      ],
    ],
    'winner_criteria' => [
      'title' => ts('Winner Criteria'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Select',
      'add' => '4.6',
      'pseudoconstant' => [
        'callback' => ['CRM_Mailing_PseudoConstant', 'abWinnerCriteria'],
      ],
    ],
    'specific_url' => [
      'title' => ts('URL for Winner Criteria'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('What specific url to track'),
      'add' => '4.6',
    ],
    'declare_winning_time' => [
      'title' => ts('Declaration Time'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('In how much time to declare winner'),
      'add' => '4.6',
    ],
    'group_percentage' => [
      'title' => ts('Group Percentage'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'add' => '4.6',
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID'),
      'add' => '4.6',
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'created_date' => [
      'title' => ts('AB Test Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'description' => ts('When was this item created'),
      'add' => '4.6',
      'default' => 'CURRENT_TIMESTAMP',
      'input_attrs' => [
        'format_type' => 'mailing',
      ],
    ],
  ],
];
