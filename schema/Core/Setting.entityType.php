<?php

return [
  'name' => 'Setting',
  'table' => 'civicrm_setting',
  'class' => 'CRM_Core_DAO_Setting',
  'getInfo' => fn() => [
    'title' => ts('Setting'),
    'title_plural' => ts('Settings'),
    'description' => ts('Table to store civicrm settings for civicrm core and components.'),
    'add' => '4.1',
  ],
  'getIndices' => fn() => [
    'index_domain_contact_name' => [
      'fields' => [
        'domain_id' => TRUE,
        'contact_id' => TRUE,
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '4.7',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Setting ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '4.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Setting Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Unique name for setting'),
      'add' => '4.1',
    ],
    'value' => [
      'title' => ts('Value'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('data associated with this group / name combo'),
      'add' => '4.1',
      'serialize' => CRM_Core_DAO::SERIALIZE_PHP,
      'input_attrs' => [
        'label' => ts('Value'),
      ],
    ],
    'domain_id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('Which Domain does this setting belong to'),
      'add' => '4.1',
      'default' => NULL,
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
        'on_delete' => 'CASCADE',
      ],
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID if the setting is localized to a contact'),
      'add' => '4.1',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'is_domain' => [
      'title' => ts('Is Domain Setting?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this setting per-domain or global?'),
      'add' => '4.1',
      'default' => FALSE,
    ],
    'component_id' => [
      'title' => ts('Component ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Component that this menu item belongs to'),
      'add' => '4.1',
      'input_attrs' => [
        'label' => ts('Component'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_component',
        'key_column' => 'id',
        'label_column' => 'name',
      ],
      'entity_reference' => [
        'entity' => 'Component',
        'key' => 'id',
      ],
    ],
    'created_date' => [
      'title' => ts('Setting Created Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('When was the setting created'),
      'add' => '4.1',
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to civicrm_contact, who created this setting'),
      'add' => '4.1',
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
  ],
];
