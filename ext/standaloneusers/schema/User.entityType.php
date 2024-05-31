<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'name' => 'User',
  'table' => 'civicrm_user_account',
  'class' => 'CRM_Standaloneusers_DAO_User',
  'getInfo' => fn() => [
    'title' => E::ts('User'),
    'title_plural' => E::ts('Users'),
    'description' => E::ts('Standalone User Accounts.'),
    'log' => TRUE,
    'label_field' => 'username',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/user',
    'update' => 'civicrm/admin/user/#?User1=[id]',
  ],
  'getIndices' => fn() => [
    'UI_username' => [
      'fields' => [
        'username' => TRUE,
      ],
      'unique' => TRUE,
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('User ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique User ID'),
      'add' => '5.67',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'email' => [
      'title' => E::ts('User Email'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Email',
      'description' => E::ts('Email (e.g. for password resets)'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
    'username' => [
      'title' => E::ts('Username'),
      'sql_type' => 'varchar(60)',
      'input_type' => 'Text',
      'required' => TRUE,
      'input_attrs' => [
        'maxlength' => 60,
      ],
    ],
    'hashed_password' => [
      'title' => E::ts('Hashed Password'),
      'sql_type' => 'varchar(128)',
      'input_type' => NULL,
      'required' => TRUE,
      'readonly' => TRUE,
      'description' => E::ts('Hashed, not plaintext password'),
      'default' => '',
      'permission' => [
        [
          'administer CiviCRM',
          'cms:administer users',
        ],
      ],
      'input_attrs' => [
        'maxlength' => 128,
      ],
    ],
    'when_created' => [
      'title' => E::ts('When Created'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'when_last_accessed' => [
      'title' => E::ts('When Last Accessed'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
    ],
    'when_updated' => [
      'title' => E::ts('When Updated'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
    ],
    'is_active' => [
      'title' => E::ts('Enabled'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'default' => TRUE,
      'input_attrs' => [
        'label' => E::ts('Enabled'),
      ],
    ],
    'timezone' => [
      'title' => E::ts('Timezone'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Select',
      'description' => E::ts('User\'s timezone'),
      'input_attrs' => [
        'maxlength' => 32,
      ],
      'pseudoconstant' => [
        'callback' => 'CRM_Standaloneusers_BAO_User::getTimeZones',
      ],
    ],
    'password_reset_token' => [
      'title' => E::ts('Password Reset Token'),
      'sql_type' => 'varchar(255)',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => E::ts('The unspent token'),
      'permission' => [
        [
          'administer CiviCRM',
          'cms:administer users',
        ],
      ],
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
  ],
];
