<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'name' => 'Role',
  'table' => 'civicrm_role',
  'class' => 'CRM_Standaloneusers_DAO_Role',
  'getInfo' => fn() => [
    'title' => E::ts('Role'),
    'title_plural' => E::ts('Roles'),
    'description' => E::ts('A Role holds a set of permissions. Roles may be granted to Users.'),
    'log' => TRUE,
    'label_field' => 'label',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/role',
    'update' => 'civicrm/admin/role#?Role1=[id]',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique Role ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => E::ts('Name'),
      'sql_type' => 'varchar(60)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Machine name for this role'),
      'input_attrs' => [
        'maxlength' => 60,
      ],
    ],
    'label' => [
      'title' => E::ts('Label'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Human friendly name for this role'),
      'input_attrs' => [
        'maxlength' => 128,
      ],
    ],
    'permissions' => [
      'title' => E::ts('Permissions'),
      'sql_type' => 'text',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('List of permissions granted by this role'),
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'permissions'],
      ],
    ],
    'is_active' => [
      'title' => E::ts('Role is active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => E::ts('Only active roles grant permissions'),
      'default' => TRUE,
      'input_attrs' => [
        'label' => E::ts('Enabled'),
      ],
    ],
  ],
];
