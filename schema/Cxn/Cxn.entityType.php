<?php

return [
  'name' => 'Cxn',
  'table' => 'civicrm_cxn',
  'class' => 'CRM_Cxn_DAO_Cxn',
  'getInfo' => fn() => [
    'title' => ts('Cxn'),
    'title_plural' => ts('Cxns'),
    'description' => ts('Connections - this is no longer used and is not visible in the UI.'),
    'add' => '4.6',
  ],
  'getIndices' => fn() => [
    'UI_appid' => [
      'fields' => [
        'app_guid' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.6',
    ],
    'UI_keypair_cxnid' => [
      'fields' => [
        'cxn_guid' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.6',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Connection ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Connection ID'),
      'add' => '4.6',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'app_guid' => [
      'title' => ts('Application GUID'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Application GUID'),
      'add' => '4.6',
    ],
    'app_meta' => [
      'title' => ts('Application Metadata (JSON)'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Application Metadata (JSON)'),
      'add' => '4.6',
    ],
    'cxn_guid' => [
      'title' => ts('Connection GUID'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Connection GUID'),
      'add' => '4.6',
    ],
    'secret' => [
      'title' => ts('Secret'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Shared secret'),
      'add' => '4.6',
      'input_attrs' => [
        'label' => ts('Secret'),
      ],
    ],
    'perm' => [
      'title' => ts('Perm'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Permissions approved for the service (JSON)'),
      'add' => '4.6',
      'input_attrs' => [
        'label' => ts('Permissions'),
      ],
    ],
    'options' => [
      'title' => ts('Options'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Options for the service (JSON)'),
      'add' => '4.6',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
      'input_attrs' => [
        'label' => ts('Options'),
      ],
    ],
    'is_active' => [
      'title' => ts('Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is connection currently enabled?'),
      'add' => '4.6',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('When was the connection was created.'),
      'add' => '4.6',
      'default' => NULL,
      'input_attrs' => [
        'label' => ts('Created Date'),
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('When the connection was created or modified.'),
      'add' => '4.6',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ],
    'fetched_date' => [
      'title' => ts('Fetched Date'),
      'sql_type' => 'timestamp',
      'input_type' => NULL,
      'description' => ts('The last time the application metadata was fetched.'),
      'add' => '4.6',
      'default' => NULL,
      'input_attrs' => [
        'label' => ts('Fetched Date'),
      ],
    ],
  ],
];
