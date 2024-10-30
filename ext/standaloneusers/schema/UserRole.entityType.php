<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'name' => 'UserRole',
  'table' => 'civicrm_user_role',
  'class' => 'CRM_Standaloneusers_DAO_UserRole',
  'getInfo' => fn() => [
    'title' => E::ts('User Role'),
    'title_plural' => E::ts('User Roles'),
    'description' => E::ts('Bridge between users and roles'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique UserRole ID'),
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'user_id' => [
      'title' => E::ts('User ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to User'),
      'input_attrs' => [
        'label' => E::ts('User'),
      ],
      'entity_reference' => [
        'entity' => 'User',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'role_id' => [
      'title' => E::ts('Role ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Role'),
      'input_attrs' => [
        'label' => E::ts('Role'),
      ],
      'entity_reference' => [
        'entity' => 'Role',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
  ],
];
