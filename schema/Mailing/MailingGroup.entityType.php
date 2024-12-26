<?php

return [
  'name' => 'MailingGroup',
  'table' => 'civicrm_mailing_group',
  'class' => 'CRM_Mailing_DAO_MailingGroup',
  'getInfo' => fn() => [
    'title' => ts('Mailing Group'),
    'title_plural' => ts('Mailing Groups'),
    'description' => ts('Stores information about the groups that participate in this mailing..'),
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Mailing Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'mailing_id' => [
      'title' => ts('Mailing ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('The ID of a previous mailing to include/exclude recipients.'),
      'input_attrs' => [
        'label' => ts('Mailing'),
      ],
      'entity_reference' => [
        'entity' => 'Mailing',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'group_type' => [
      'title' => ts('Mailing Group Type'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'description' => ts('Are the members of the group included or excluded?.'),
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'getMailingGroupTypes'],
      ],
    ],
    'entity_table' => [
      'title' => ts('Mailing Group Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Name of table where item being referenced is stored.'),
      'pseudoconstant' => [
        'callback' => ['CRM_Mailing_BAO_Mailing', 'mailingGroupEntityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('Mailing Group Entity'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign key to the referenced item.'),
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'search_id' => [
      'title' => ts('Mailing Group Search'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => ts('The filtering search. custom search id or -1 for civicrm api search'),
    ],
    'search_args' => [
      'title' => ts('Mailing Group Search Arguments'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('The arguments to be sent to the search function'),
    ],
  ],
];
