<?php

return [
  'name' => 'ACLContactCache',
  'table' => 'civicrm_acl_contact_cache',
  'class' => 'CRM_Contact_DAO_ACLContactCache',
  'getInfo' => fn() => [
    'title' => ts('ACLContact Cache'),
    'title_plural' => ts('ACLContact Caches'),
    'description' => ts('Join table cache for contacts that a user has permission on.'),
    'add' => '3.1',
  ],
  'getIndices' => fn() => [
    'UI_user_contact_operation' => [
      'fields' => [
        'user_id' => TRUE,
        'contact_id' => TRUE,
        'operation' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '3.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('ACL Contact Cache ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('primary key'),
      'add' => '3.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'user_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('FK to civicrm_contact (could be null for anon user)'),
      'add' => '3.1',
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('FK to civicrm_contact'),
      'add' => '3.1',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
    ],
    'operation' => [
      'title' => ts('Operation'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('What operation does this user have permission on?'),
      'add' => '1.6',
      'pseudoconstant' => [
        'callback' => ['CRM_ACL_BAO_ACL', 'operation'],
      ],
    ],
  ],
];
