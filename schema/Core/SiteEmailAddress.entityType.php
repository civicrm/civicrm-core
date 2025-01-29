<?php

return [
  'name' => 'SiteEmailAddress',
  'table' => 'civicrm_site_email_address',
  'class' => 'CRM_Core_DAO_SiteEmailAddress',
  'getInfo' => fn() => [
    'title' => ts('Site Email Address'),
    'title_plural' => ts('Site Email Addresses'),
    'description' => ts('Sender addresses to use for outbound emails.'),
    'log' => TRUE,
    'add' => '6.0',
    'icon' => 'fa-envelope',
    'label_field' => 'display_name',
  ],
  'getPaths' => fn() => [],
  'getIndices' => fn() => [
    'index_domain_id_is_default' => [
      'fields' => [
        'domain_id' => TRUE,
        'is_default' => TRUE,
      ],
      'add' => '6.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Email Address ID'),
      'add' => '6.0',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'display_name' => [
      'title' => ts('Display Name'),
      'sql_type' => 'varchar(63)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Full name of the sender'),
      'add' => '6.0',
    ],
    'email' => [
      'title' => ts('Email'),
      'sql_type' => 'varchar(254)',
      'input_type' => 'Email',
      'description' => ts('Sender email address'),
      'add' => '6.0',
      'required' => TRUE,
      'input_attrs' => [
        'size' => '30',
      ],
    ],
    'description' => [
      'title' => ts('Description'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Purpose of this email address'),
      'add' => '6.0',
      'input_attrs' => [
        'rows' => 4,
        'cols' => 60,
      ],
    ],
    'is_active' => [
      'title' => ts('Enabled'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'description' => ts('Is this email address enabled?'),
      'add' => '6.0',
      'required' => TRUE,
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'is_default' => [
      'title' => ts('Default'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'description' => ts('Is this the default email for this domain?'),
      'add' => '6.0',
      'required' => TRUE,
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Default'),
      ],
    ],
    'domain_id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Which Domain is this option value for'),
      'add' => '6.0',
      'required' => TRUE,
      'input_attrs' => [
        'label' => ts('Domain'),
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
  ],
];
