<?php

return [
  'name' => 'OptionGroup',
  'table' => 'civicrm_option_group',
  'class' => 'CRM_Core_DAO_OptionGroup',
  'getInfo' => fn() => [
    'title' => ts('Option Group'),
    'title_plural' => ts('Option Groups'),
    'description' => ts('Table of option groups'),
    'log' => TRUE,
    'add' => '1.5',
    'label_field' => 'title',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/options?action=add&reset=1',
    'update' => 'civicrm/admin/options?action=update&reset=1&id=[id]',
  ],
  'getIndices' => fn() => [
    'UI_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.1',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Option Group ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Option Group ID'),
      'add' => '1.5',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Option Group Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Option group name. Used as selection key by class properties which lookup options in civicrm_option_value.'),
      'add' => '1.5',
    ],
    'title' => [
      'title' => ts('Option Group Title'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'localizable' => TRUE,
      'description' => ts('Option Group title.'),
      'add' => '1.5',
    ],
    'description' => [
      'title' => ts('Option Group Description'),
      'sql_type' => 'text',
      'input_type' => 'Text',
      'localizable' => TRUE,
      'description' => ts('Option group description.'),
      'add' => '1.5',
    ],
    'data_type' => [
      'title' => ts('Data Type'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Select',
      'description' => ts('Type of data stored by this option group.'),
      'add' => '4.7',
      'pseudoconstant' => [
        'callback' => ['CRM_Utils_Type', 'dataTypes'],
      ],
    ],
    'is_reserved' => [
      'title' => ts('Option Group Is Reserved'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this a predefined system option group (i.e. it can not be deleted)?'),
      'add' => '1.5',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Reserved'),
      ],
    ],
    'is_active' => [
      'title' => ts('Enabled'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this option group active?'),
      'add' => '1.5',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'is_locked' => [
      'title' => ts('Option Group Is Locked'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('A lock to remove the ability to add new options via the UI.'),
      'add' => '4.5',
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Locked'),
      ],
    ],
    'option_value_fields' => [
      'title' => ts('Option Value Fields'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Select',
      'description' => ts('Which optional columns from the option_value table are in use by this group.'),
      'add' => '5.49',
      'default' => 'name,label,description',
      'serialize' => CRM_Core_DAO::SERIALIZE_COMMA,
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'optionValueFields'],
      ],
    ],
  ],
];
