<?php

return [
  'name' => 'MembershipPayment',
  'table' => 'civicrm_membership_payment',
  'class' => 'CRM_Member_DAO_MembershipPayment',
  'getInfo' => fn() => [
    'title' => ts('Membership Payment'),
    'title_plural' => ts('Membership Payments'),
    'description' => ts('Membership Payment'),
    'log' => TRUE,
    'add' => '1.5',
  ],
  'getIndices' => fn() => [
    'UI_contribution_membership' => [
      'fields' => [
        'contribution_id' => TRUE,
        'membership_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Membership Payment ID'),
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
    'contribution_id' => [
      'title' => ts('Contribution ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to contribution table.'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Contribution'),
      ],
      'entity_reference' => [
        'entity' => 'Contribution',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
