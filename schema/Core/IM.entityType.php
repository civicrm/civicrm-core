<?php

return [
  'name' => 'IM',
  'table' => 'civicrm_im',
  'class' => 'CRM_Core_DAO_IM',
  'getInfo' => fn() => [
    'title' => ts('Instant Messaging'),
    'title_plural' => ts('Instant Messaging'),
    'description' => ts('IM information for a specific location.'),
    'log' => TRUE,
    'add' => '1.1',
    'icon' => 'fa-comments-o',
    'label_field' => 'name',
  ],
  'getIndices' => fn() => [
    'index_location_type' => [
      'fields' => [
        'location_type_id' => TRUE,
      ],
      'add' => '2.0',
    ],
    'UI_provider_id' => [
      'fields' => [
        'provider_id' => TRUE,
      ],
      'add' => '1.6',
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
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Instant Messenger ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique IM ID'),
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
      'title' => ts('IM Location Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which Location does this email belong to.'),
      'add' => '2.0',
      'pseudoconstant' => [
        'table' => 'civicrm_location_type',
        'key_column' => 'id',
        'name_column' => 'name',
        'description_column' => 'description',
        'label_column' => 'display_name',
        'abbr_column' => 'vcard_name',
      ],
    ],
    'name' => [
      'title' => ts('IM Screen Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('IM screen name'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'provider_id' => [
      'title' => ts('IM Provider'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which IM Provider does this screen name belong to.'),
      'add' => '1.1',
      'pseudoconstant' => [
        'option_group_name' => 'instant_messenger_service',
      ],
    ],
    'is_primary' => [
      'title' => ts('Is Primary'),
      'sql_type' => 'boolean',
      'input_type' => 'Radio',
      'required' => TRUE,
      'description' => ts('Is this the primary IM for this contact and location.'),
      'add' => '1.1',
      'default' => FALSE,
    ],
    'is_billing' => [
      'title' => ts('Is IM Billing?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this the billing?'),
      'add' => '2.0',
      'default' => FALSE,
    ],
  ],
];
