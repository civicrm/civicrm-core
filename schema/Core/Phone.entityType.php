<?php

return [
  'name' => 'Phone',
  'table' => 'civicrm_phone',
  'class' => 'CRM_Core_DAO_Phone',
  'getInfo' => fn() => [
    'title' => ts('Phone'),
    'title_plural' => ts('Phones'),
    'description' => ts('Phone information for a specific location.'),
    'log' => TRUE,
    'add' => '1.1',
    'icon' => 'fa-phone',
    'label_field' => 'phone',
  ],
  'getIndices' => fn() => [
    'index_location_type' => [
      'fields' => [
        'location_type_id' => TRUE,
      ],
      'add' => '2.0',
    ],
    'index_is_primary' => [
      'fields' => [
        'is_primary' => TRUE,
      ],
      'add' => '2.0',
    ],
    'index_is_billing' => [
      'fields' => [
        'is_billing' => TRUE,
      ],
      'add' => '2.0',
    ],
    'UI_mobile_provider_id' => [
      'fields' => [
        'mobile_provider_id' => TRUE,
      ],
      'add' => '1.6',
    ],
    'index_phone_numeric' => [
      'fields' => [
        'phone_numeric' => TRUE,
      ],
      'add' => '4.3',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Phone ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Phone ID'),
      'add' => '1.1',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to Contact ID'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'CASCADE',
      ],
    ],
    'location_type_id' => [
      'title' => ts('Location Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which Location does this phone belong to.'),
      'add' => '2.0',
      'input_attrs' => [
        'label' => ts('Location Type'),
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_location_type',
        'key_column' => 'id',
        'name_column' => 'name',
        'description_column' => 'description',
        'label_column' => 'display_name',
        'abbr_column' => 'vcard_name',
      ],
    ],
    'is_primary' => [
      'title' => ts('Is Primary'),
      'sql_type' => 'boolean',
      'input_type' => 'Radio',
      'required' => TRUE,
      'description' => ts('Is this the primary phone for this contact and location.'),
      'add' => '1.1',
      'default' => FALSE,
    ],
    'is_billing' => [
      'title' => ts('Is Billing Phone'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this the billing?'),
      'add' => '2.0',
      'default' => FALSE,
    ],
    'mobile_provider_id' => [
      'title' => ts('Mobile Provider'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'deprecated' => TRUE,
      'description' => ts('Which Mobile Provider does this phone belong to.'),
      'add' => '1.1',
    ],
    'phone' => [
      'title' => ts('Phone'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Text',
      'description' => ts('Complete phone number.'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Phone'),
      ],
    ],
    'phone_ext' => [
      'title' => ts('Phone Extension'),
      'sql_type' => 'varchar(16)',
      'input_type' => 'Text',
      'description' => ts('Optional extension for a phone number.'),
      'add' => '3.3',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '4',
      ],
    ],
    'phone_numeric' => [
      'title' => ts('Phone Numeric'),
      'sql_type' => 'varchar(32)',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('Phone number stripped of all whitespace, letters, and punctuation.'),
      'add' => '4.3',
      'usage' => [
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Phone (Numbers only)'),
      ],
    ],
    'phone_type_id' => [
      'title' => ts('Phone Type ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which type of phone does this number belongs.'),
      'add' => '2.2',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Phone Type'),
      ],
      'pseudoconstant' => [
        'option_group_name' => 'phone_type',
      ],
    ],
  ],
];
