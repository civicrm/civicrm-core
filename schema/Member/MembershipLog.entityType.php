<?php

return [
  'name' => 'MembershipLog',
  'table' => 'civicrm_membership_log',
  'class' => 'CRM_Member_DAO_MembershipLog',
  'getInfo' => fn() => [
    'title' => ts('Membership Log'),
    'title_plural' => ts('Membership Logs'),
    'description' => ts('Logs actions which affect a Membership record (signup, status override, renewal, etc.)'),
    'log' => TRUE,
    'add' => '1.5',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Membership Log ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '1.5',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'membership_id' => [
      'title' => ts('Membership ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('FK to Membership table'),
      'add' => '1.5',
      'input_attrs' => [
        'label' => ts('Membership'),
      ],
      'entity_reference' => [
        'entity' => 'Membership',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'status_id' => [
      'title' => ts('Membership Status ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('New status assigned to membership by this action. FK to Membership Status'),
      'add' => '1.5',
      'input_attrs' => [
        'label' => ts('Membership Status'),
      ],
      'entity_reference' => [
        'entity' => 'MembershipStatus',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'start_date' => [
      'title' => ts('Membership Log Start Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('New membership period start date'),
      'add' => '1.5',
    ],
    'end_date' => [
      'title' => ts('Membership Log End Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('New membership period expiration date.'),
      'add' => '1.5',
    ],
    'modified_id' => [
      'title' => ts('Modified By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('FK to Contact ID of person under whose credentials this data modification was made.'),
      'add' => '1.5',
      'input_attrs' => [
        'label' => ts('Modified By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'modified_date' => [
      'title' => ts('Membership Change Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Date this membership modification action was logged.'),
      'add' => '1.5',
    ],
    'membership_type_id' => [
      'title' => ts('Membership Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Membership Type.'),
      'add' => '3.4',
      'input_attrs' => [
        'label' => ts('Membership Type'),
      ],
      'entity_reference' => [
        'entity' => 'MembershipType',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'max_related' => [
      'title' => ts('Maximum Related Memberships'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'description' => ts('Maximum number of related memberships.'),
      'add' => '4.3',
    ],
  ],
];
