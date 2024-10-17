<?php

return [
  'name' => 'SiteToken',
  'table' => 'civicrm_site_token',
  'class' => 'CRM_Core_DAO_SiteToken',
  'token_class' => 'CRM_Core_SiteTokens',
  'getInfo' => fn() => [
    'title' => ts('Site Token'),
    'title_plural' => ts('Site Tokens'),
    'description' => ts('Site-wide tokens.'),
    'add' => 5.76,
    'log' => TRUE,
    'icon' => 'fa-code',
  ],
  'getIndices' => fn() => [
    'UI_name_domain_id' => [
      'fields' => [
        'name' => TRUE,
        'domain_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '5.76',
    ],
    'UI_label_domain_id' => [
      'fields' => [
        'label' => TRUE,
        'domain_id' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '5.76',
    ],
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/sitetoken/edit?action=add&reset=1',
    'update' => 'civicrm/admin/sitetoken/edit#?SiteToken1=[id]',
    'browse' => 'civicrm/admin/sitetoken?action=browse&reset=1',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Site Token ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'domain_id' => [
      'title' => ts('Domain'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'entity_reference' => [
        'entity' => 'Domain',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_domain',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
    ],
    'name' => [
      'title' => ts('Token Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Token string, e.g. {site.[name]}'),
      'input_attrs' => [
        'maxlength' => 64,
      ],
    ],
    'label' => [
      'title' => ts('Token Label'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('User-visible label in token UI'),
      'input_attrs' => [
        'label' => ts('Label'),
        'maxlength' => 255,
      ],
    ],
    'body_html' => [
      'title' => ts('Token Value (HTML)'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Value of the token in html format.'),
      'input_attrs' => [
        'rows' => 8,
        'cols' => 80,
      ],
    ],
    'body_text' => [
      'title' => ts('Token Value (Text)'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Value of the token in text format.'),
      'input_attrs' => [
        'rows' => 8,
        'cols' => 80,
        'label' => ts('Body in Text Format'),
      ],
    ],
    'is_active' => [
      'title' => ts('Token Is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this token active?'),
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'is_reserved' => [
      'title' => ts('Token Is Reserved?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this token reserved?'),
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Reserved'),
      ],
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Contact responsible for creating this token'),
      'add' => '5.76',
      'default_callback' => ['CRM_Core_Session', 'getLoggedInContactID'],
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'modified_id' => [
      'title' => ts('Modified By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('FK to contact table.'),
      'add' => '5.76',
      'default_callback' => ['CRM_Core_Session', 'getLoggedInContactID'],
      'input_attrs' => [
        'label' => ts('Modified By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When the token was created or modified or deleted.'),
      'add' => '4.7',
      'unique_name' => 'site_token_modified_date',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ],
  ],
];
