<?php

return [
  'name' => 'PrintLabel',
  'table' => 'civicrm_print_label',
  'class' => 'CRM_Core_DAO_PrintLabel',
  'getInfo' => fn() => [
    'title' => ts('Print Label'),
    'title_plural' => ts('Print Labels'),
    'description' => ts('Table to store the labels created by user.'),
    'add' => '4.4',
    'label_field' => 'title',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/badgelayout/add?reset=1&action=add',
    'delete' => 'civicrm/admin/badgelayout/add?reset=1&action=delete&id=[id]',
    'update' => 'civicrm/admin/badgelayout/add?reset=1&action=update&id=[id]',
    'browse' => 'civicrm/admin/badgelayout',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Print Label ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'add' => '4.4',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'title' => [
      'title' => ts('Title'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('User title for this label layout'),
      'add' => '4.4',
    ],
    'name' => [
      'title' => ts('Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('variable name/programmatic handle for this field.'),
      'add' => '4.4',
    ],
    'description' => [
      'title' => ts('Description'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Description of this label layout'),
      'add' => '4.4',
      'input_attrs' => [
        'label' => ts('Description'),
      ],
    ],
    'label_format_name' => [
      'title' => ts('Label Format'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('This refers to name column of civicrm_option_value row in name_badge option group'),
      'add' => '4.4',
      'pseudoconstant' => [
        'option_group_name' => 'name_badge',
      ],
    ],
    'label_type_id' => [
      'title' => ts('Label Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Implicit FK to civicrm_option_value row in NEW label_type option group'),
      'add' => '4.4',
      'pseudoconstant' => [
        'option_group_name' => 'label_type',
      ],
    ],
    'data' => [
      'title' => ts('Data'),
      'sql_type' => 'longtext',
      'input_type' => 'TextArea',
      'description' => ts('contains json encode configurations options'),
      'add' => '4.4',
      'serialize' => CRM_Core_DAO::SERIALIZE_JSON,
      'input_attrs' => [
        'label' => ts('Data'),
      ],
    ],
    'is_default' => [
      'title' => ts('Label is Default?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this default?'),
      'add' => '4.4',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Default'),
      ],
    ],
    'is_active' => [
      'title' => ts('Label Is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this option active?'),
      'add' => '4.4',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'is_reserved' => [
      'title' => ts('Is Label Reserved?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this reserved label?'),
      'add' => '4.4',
      'default' => TRUE,
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to civicrm_contact, who created this label layout'),
      'add' => '4.4',
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
