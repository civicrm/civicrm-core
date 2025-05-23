<?php

return [
  'name' => 'OpenID',
  'table' => 'civicrm_openid',
  'class' => 'CRM_Core_DAO_OpenID',
  'getInfo' => fn() => [
    'title' => ts('Open ID'),
    'title_plural' => ts('Open IDs'),
    'description' => ts('OpenID information for a specific location.'),
    'icon' => 'fa-openid',
    'add' => '2.0',
  ],
  'getIndices' => fn() => [
    'index_location_type' => [
      'fields' => [
        'location_type_id' => TRUE,
      ],
      'add' => '2.0',
    ],
    'UI_openid' => [
      'fields' => [
        'openid' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '2.0',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Open ID identifier'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique OpenID ID'),
      'add' => '2.0',
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
      'title' => ts('OpenID Location Type'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Which Location does this email belong to.'),
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
    'openid' => [
      'title' => ts('OpenID'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Url',
      'description' => ts('the OpenID (or OpenID-style http://username.domain/) unique identifier for this contact mainly used for logging in to CiviCRM'),
      'add' => '2.0',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'allowed_to_login' => [
      'title' => ts('Allowed to login?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Whether or not this user is allowed to login'),
      'add' => '2.0',
      'default' => FALSE,
    ],
    'is_primary' => [
      'title' => ts('Is Primary'),
      'sql_type' => 'boolean',
      'input_type' => 'Radio',
      'required' => TRUE,
      'description' => ts('Is this the primary email for this contact and location.'),
      'add' => '2.0',
      'default' => FALSE,
    ],
  ],
];
