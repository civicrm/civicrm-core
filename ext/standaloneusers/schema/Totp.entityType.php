<?php
use CRM_Standaloneusers_ExtensionUtil as E;

return [
  'name' => 'Totp',
  'table' => 'civicrm_totp',
  'class' => 'CRM_Standaloneusers_DAO_Totp',
  'getInfo' => fn() => [
    'title' => E::ts('TOTP'),
    'title_plural' => E::ts('TOTPs'),
    'description' => E::ts('Time based One Time Password keys'),
    'log' => TRUE,
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('ID'),
      'description' => E::ts('Unique TOTP ID'),
      'sql_type' => 'int(10) unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'user_id' => [
      'title' => E::ts('User ID'),
      'description' => E::ts('Reference to User (UFMatch) ID'),
      'sql_type' => 'int(10) unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'entity_reference' => [
        'entity' => 'UFMatch',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'seed' => [
      'title' => E::ts('Encrypted Base64 encoded TOTP Seed'),
      'sql_type' => 'varchar(512)',
      'required' => TRUE,
    ],
    'hash' => [
      'title' => E::ts('Hash algorithm used'),
      'sql_type' => 'varchar(20)',
      'required' => TRUE,
      'input_attrs' => [
        'maxlength' => 20,
      ],
      'default' => '"sha1"',
    ],
    'period' => [
      'title' => E::ts('Seconds each code lasts'),
      'sql_type' => 'INT(1) UNSIGNED',
      'required' => TRUE,
      'default' => '30',
    ],
    'length' => [
      'title' => E::ts('Length of codes'),
      'sql_type' => 'INT(1) UNSIGNED',
      'required' => TRUE,
      'default' => '6',
    ],
  ],
  'getIndices' => fn() => [],
  'getPaths' => fn() => [],
];
