<?php

return [
  'name' => 'ACLEntityRole',
  'table' => 'civicrm_acl_entity_role',
  'class' => 'CRM_ACL_DAO_ACLEntityRole',
  'getInfo' => fn() => [
    'title' => ts('ACL Role Assignment'),
    'title_plural' => ts('ACL Role Assignments'),
    'description' => ts('Join table for Contacts and Groups to ACL Roles'),
    'add' => '1.6',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/acl/entityrole/edit?reset=1&action=add',
    'delete' => 'civicrm/acl/entityrole/edit?reset=1&action=delete&id=[id]',
    'update' => 'civicrm/acl/entityrole/edit?reset=1&action=update&id=[id]',
    'browse' => 'civicrm/acl/entityrole',
  ],
  'getIndices' => fn() => [
    'index_role' => [
      'fields' => [
        'acl_role_id' => TRUE,
      ],
      'add' => '1.6',
    ],
    'index_entity' => [
      'fields' => [
        'entity_table' => TRUE,
        'entity_id' => TRUE,
      ],
      'add' => '1.6',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Entity Role'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique table ID'),
      'add' => '1.6',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'acl_role_id' => [
      'title' => ts('ACL Role ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Foreign Key to ACL Role (which is an option value pair and hence an implicit FK)'),
      'add' => '1.6',
      'pseudoconstant' => [
        'option_group_name' => 'acl_role',
      ],
    ],
    'entity_table' => [
      'title' => ts('Entity Table'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Table of the object joined to the ACL Role (Contact or Group)'),
      'add' => '1.6',
      'pseudoconstant' => [
        'callback' => ['CRM_ACL_BAO_ACLEntityRole', 'entityTables'],
      ],
    ],
    'entity_id' => [
      'title' => ts('ACL Entity ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('ID of the group/contact object being joined'),
      'add' => '1.6',
      'entity_reference' => [
        'dynamic_entity' => 'entity_table',
        'key' => 'id',
      ],
    ],
    'is_active' => [
      'title' => ts('ACL Entity Role is Active'),
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
  ],
];
