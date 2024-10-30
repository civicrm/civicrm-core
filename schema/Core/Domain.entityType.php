<?php

return [
  'name' => 'Domain',
  'table' => 'civicrm_domain',
  'class' => 'CRM_Core_DAO_Domain',
  'getInfo' => fn() => [
    'title' => ts('Domain'),
    'title_plural' => ts('Domains'),
    'description' => ts('Top-level hierarchy to support multi-org/domain installations. Define domains for multi-org installs, else all contacts belong to one domain.'),
    'add' => '1.1',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Domain ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Domain Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Name of Domain / Organization'),
      'add' => '1.1',
    ],
    'description' => [
      'title' => ts('Domain Description'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Description of Domain.'),
      'add' => '1.1',
    ],
    'version' => [
      'title' => ts('CiviCRM Version'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Text',
      'description' => ts('The civicrm version this instance is running'),
      'add' => '2.0',
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID. This is specifically not an FK to avoid circular constraints'),
      'add' => '4.3',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
      ],
    ],
    'locales' => [
      'title' => ts('Supported Languages'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('list of locales supported by the current db state (NULL for single-lang install)'),
      'add' => '2.1',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_TRIMMED,
    ],
    'locale_custom_strings' => [
      'title' => ts('Language Customizations'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Locale specific string overrides'),
      'add' => '3.2',
      'serialize' => CRM_Core_DAO::SERIALIZE_PHP,
    ],
  ],
];
