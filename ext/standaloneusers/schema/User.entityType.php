<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'name' => 'User',
  'table' => 'civicrm_uf_match',
  'class' => 'CRM_Standaloneusers_DAO_User',
  'getInfo' => fn() => [
    'title' => E::ts('User'),
    'title_plural' => E::ts('Users'),
    'description' => E::ts('Standalone User Account. In Standalone this is a superset of the original civicrm_uf_match table.'),
    'log' => TRUE,
    'label_field' => 'username',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/user',
    'update' => 'civicrm/admin/user/#?User1=[id]',
  ],
  'getIndices' => fn() => [
    'I_civicrm_uf_match_uf_id' => [
      'fields' => [
        'uf_id' => TRUE,
      ],
      'add' => '3.3',
    ],
    'UI_username' => [
      'fields' => [
        'username' => TRUE,
      ],
      'unique' => TRUE,
    ],
    'UI_uf_name_domain_id' => [
      'fields' => [
        'uf_name' => TRUE,
        'domain_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.1',
    ],
    'UI_contact_domain_id' => [
      'fields' => [
        'contact_id' => TRUE,
        'domain_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.6',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('UF Match ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Unique User ID'),
      'add' => '5.67',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'domain_id' => [
      'title' => E::ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => E::ts('Which Domain is this match entry for'),
      'add' => '3.0',
      'input_attrs' => [
        'label' => E::ts('Domain'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_domain',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'Domain',
        'key' => 'id',
      ],
    ],
    'uf_id' => [
      'title' => E::ts('CMS ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('UF ID. Redundant in Standalone. Needs to be identical to id.'),
      'add' => '1.1',
      'default' => 0,
    ],
    'uf_name' => [
      'title' => E::ts('User Email'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Email',
      'description' => E::ts('Email (e.g. for password resets)'),
      'input_attrs' => [
        'maxlength' => 255,
      ],
    ],
    'contact_id' => [
      'title' => E::ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('FK to Contact ID'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => E::ts('Contact'),
        'filter' => [
          'contact_type' => 'Individual',
        ],
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
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
        'callback' => ['CRM_Standaloneusers_BAO_User', 'getTimeZones'],
      ],
    ],
    'language' => [
      'title' => E::ts('Preferred Language'),
      'sql_type' => 'varchar(5)',
      'input_type' => 'Select',
      'description' => E::ts('UI language preferred by the given user/contact'),
      'add' => '2.1',
      'input_attrs' => [
        'maxlength' => 5,
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Standaloneusers_BAO_User', 'getPreferredLanguages'],
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
