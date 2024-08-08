<?php

return [
  'name' => 'ACLCache',
  'table' => 'civicrm_acl_cache',
  'class' => 'CRM_ACL_DAO_ACLCache',
  'getInfo' => fn() => [
    'title' => ts('ACLCache'),
    'title_plural' => ts('ACLCaches'),
    'description' => ts('Cache for acls and contacts'),
    'add' => '1.6',
  ],
  'getIndices' => fn() => [
    'index_contact_id' => [
      'fields' => [
        'contact_id' => TRUE,
      ],
      'add' => '5.31',
    ],
    'index_acl_id' => [
      'fields' => [
        'acl_id' => TRUE,
      ],
      'add' => '1.6',
    ],
    'index_modified_date' => [
      'fields' => [
        'modified_date' => TRUE,
      ],
      'add' => '5.22',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Cache ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique table ID'),
      'add' => '1.6',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Foreign Key to Contact'),
      'add' => '1.6',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
    ],
    'acl_id' => [
      'title' => ts('ACL ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Foreign Key to ACL'),
      'add' => '1.6',
      'input_attrs' => [
        'label' => ts('ACL'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_acl',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
    ],
    'modified_date' => [
      'title' => ts('Cache Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('When was this cache entry last modified'),
      'add' => '1.6',
    ],
  ],
];
