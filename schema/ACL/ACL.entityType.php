<?php

return [
  'name' => 'ACL',
  'table' => 'civicrm_acl',
  'class' => 'CRM_ACL_DAO_ACL',
  'getInfo' => fn() => [
    'title' => ts('ACL'),
    'title_plural' => ts('ACLs'),
    'description' => ts('Access Control List'),
    'add' => '1.6',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/acl/edit?reset=1&action=add',
    'delete' => 'civicrm/acl/delete?reset=1&action=delete&id=[id]',
    'update' => 'civicrm/acl/edit?reset=1&action=edit&id=[id]',
    'browse' => 'civicrm/acl',
  ],
  'getIndices' => fn() => [
    'index_acl_id' => [
      'fields' => [
        'acl_id' => TRUE,
      ],
      'add' => '1.6',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('ACL ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique table ID'),
      'add' => '1.6',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('ACL Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('ACL Name.'),
      'add' => '1.6',
    ],
    'deny' => [
      'title' => ts('Deny ACL?'),
      'sql_type' => 'boolean',
      'input_type' => 'Radio',
      'required' => TRUE,
      'description' => ts('Is this ACL entry Allow (0) or Deny (1) ?'),
      'add' => '1.6',
      'default' => FALSE,
    ],
    'entity_table' => [
      'title' => ts('ACL Entity'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Table of the object possessing this ACL entry (Contact, Group, or ACL Group)'),
      'add' => '1.6',
    ],
    'entity_id' => [
      'title' => ts('Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('ID of the object possessing this ACL'),
      'add' => '1.6',
      'pseudoconstant' => [
        'option_group_name' => 'acl_role',
      ],
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'operation' => [
      'title' => ts('ACL Operation'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('What operation does this ACL entry control?'),
      'add' => '1.6',
      'pseudoconstant' => [
        'callback' => ['CRM_ACL_BAO_ACL', 'operation'],
      ],
    ],
    'object_table' => [
      'title' => ts('ACL Object'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'description' => ts('The table of the object controlled by this ACL entry'),
      'add' => '1.6',
      'input_attrs' => [
        'label' => ts('Type of Data'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_ACL_BAO_ACL', 'getObjectTableOptions'],
      ],
    ],
    'object_id' => [
      'title' => ts('ACL Object ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('The ID of the object controlled by this ACL entry'),
      'add' => '1.6',
      'input_attrs' => [
        'label' => ts('Which Data'),
        'control_field' => 'object_table',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_ACL_BAO_ACL', 'getObjectIdOptions'],
        'prefetch' => 'disabled',
      ],
    ],
    'acl_table' => [
      'title' => ts('ACL Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('If this is a grant/revoke entry, what table are we granting?'),
      'add' => '1.6',
    ],
    'acl_id' => [
      'title' => ts('ACL Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => ts('ID of the ACL or ACL group being granted/revoked'),
      'add' => '1.6',
    ],
    'is_active' => [
      'title' => ts('ACL Is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this property active?'),
      'add' => '1.6',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'priority' => [
      'title' => ts('Priority'),
      'sql_type' => 'int',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '5.64',
      'default' => 0,
    ],
  ],
];
