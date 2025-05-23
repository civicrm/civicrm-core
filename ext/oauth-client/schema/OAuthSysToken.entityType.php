<?php
use CRM_OAuth_ExtensionUtil as E;

return [
  'name' => 'OAuthSysToken',
  'table' => 'civicrm_oauth_systoken',
  'class' => 'CRM_OAuth_DAO_OAuthSysToken',
  'getInfo' => fn() => [
    'title' => E::ts('OAuth System Token'),
    'title_plural' => E::ts('OAuth System Tokens'),
    'add' => '5.32',
  ],
  'getIndices' => fn() => [
    'UI_tag' => [
      'fields' => [
        'tag' => TRUE,
      ],
      'add' => '5.32',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => E::ts('Token ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => E::ts('Token ID'),
      'add' => '5.32',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'tag' => [
      'title' => E::ts('Tag'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('The tag specifies how this token will be used.'),
      'add' => '5.32',
    ],
    'client_id' => [
      'title' => E::ts('Client ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => E::ts('Client ID'),
      'add' => '5.32',
      'entity_reference' => [
        'entity' => 'OAuthClient',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'grant_type' => [
      'title' => E::ts('Grant type'),
      'sql_type' => 'varchar(31)',
      'input_type' => 'Text',
      'description' => E::ts('Ex: authorization_code'),
      'add' => '5.32',
    ],
    'scopes' => [
      'title' => E::ts('Scopes'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('List of scopes addressed by this token'),
      'add' => '5.32',
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND'),
    ],
    'token_type' => [
      'title' => E::ts('Token Type'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('Ex: Bearer or MAC'),
      'add' => '5.32',
    ],
    'access_token' => [
      'title' => E::ts('Access Token'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Token to present when accessing resources'),
      'add' => '5.32',
      'permission' => [
        [
          'manage OAuth client secrets',
        ],
      ],
    ],
    'expires' => [
      'title' => E::ts('Expiration time'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'description' => E::ts('Expiration time for the access_token (seconds since epoch)'),
      'add' => '4.7',
      'default' => 0,
    ],
    'refresh_token' => [
      'title' => E::ts('Refresh Token'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Token to present when refreshing the access_token'),
      'add' => '5.32',
      'permission' => [
        [
          'manage OAuth client secrets',
        ],
      ],
    ],
    'resource_owner_name' => [
      'title' => E::ts('Resource Owner Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => E::ts('Identifier for the resource owner. Structure varies by service.'),
      'add' => '5.32',
    ],
    'resource_owner' => [
      'title' => E::ts('Resource Owner'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('Cached details describing the resource owner'),
      'add' => '5.32',
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'),
    ],
    'error' => [
      'title' => E::ts('Error'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('List of scopes addressed by this token'),
      'add' => '5.32',
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'),
    ],
    'raw' => [
      'title' => E::ts('Raw token'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => E::ts('The token response data, per AccessToken::jsonSerialize'),
      'add' => '5.32',
      'serialize' => constant('CRM_Core_DAO::SERIALIZE_JSON'),
    ],
    'created_date' => [
      'title' => E::ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => E::ts('When the token was created.'),
      'add' => '5.32',
      'default' => 'CURRENT_TIMESTAMP',
    ],
    'modified_date' => [
      'title' => E::ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => E::ts('When the token was created or modified.'),
      'add' => '5.32',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    ],
  ],
];
