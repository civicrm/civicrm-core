<?php
use CRM_OAuth_ExtensionUtil as E;

return [
  'name' => 'OAuthClient',
  'table' => 'civicrm_oauth_client',
  'class' => 'CRM_OAuth_DAO_OAuthClient',
  'getInfo' => fn() => [
    'title' => E::ts('OAuth Client'),
    'title_plural' => E::ts('OAuth Clients'),
    'add' => '5.32',
  ],
  'getIndices' => fn() => [
    'UI_provider' => [
      'fields' => [
        'provider' => TRUE,
      ],
      'add' => '5.32',
    ],
    'UI_guid' => [
      'fields' => [
        'guid' => TRUE,
      ],
      'add' => '5.32',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('Internal Client ID'),
      'sql_type' => 'int unsigned',
      'required' => TRUE,
      'input_type' => 'Number',
      'description' => E::ts('Internal Client ID'),
      'add' => '5.32',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'provider' => [
      'title' => E::ts('Provider'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => E::ts('Provider'),
      'add' => '5.32',
      'pseudoconstant' => [
        'callback' => 'CRM_OAuth_BAO_OAuthClient::getProviders',
      ],
    ],
    'guid' => [
      'title' => E::ts('Client ID'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => E::ts('Client ID'),
      'add' => '5.32',
    ],
    'tenant' => [
      'title' => E::ts('Tenant ID'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('Tenant ID'),
      'add' => '5.57',
      'permission' => [
        'manage OAuth client',
      ],
    ],
    'secret' => [
      'title' => E::ts('Client Secret'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Client Secret'),
      'add' => '5.32',
      'permission' => [
        'manage OAuth client',
      ],
    ],
    'options' => [
      'title' => E::ts('Options'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Extra override options for the service (JSON)'),
      'add' => '5.32',
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'),
      'permission' => [
        'manage OAuth client',
      ],
    ],
    'is_active' => [
      'title' => E::ts('Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => E::ts('Is the client currently enabled?'),
      'add' => '5.32',
      'default' => TRUE,
      'input_attrs' => [
        'label' => E::ts('Enabled'),
      ],
    ],
    'created_date' => [
      'title' => E::ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => E::ts('When the client was created.'),
      'add' => '5.32',
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'modified_date' => [
      'title' => E::ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'required' => TRUE,
      'description' => E::ts('When the client was created or modified.'),
      'add' => '5.32',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],
  ],
];
