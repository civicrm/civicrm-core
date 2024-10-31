<?php

return [
  'name' => 'Email',
  'table' => 'civicrm_email',
  'class' => 'CRM_Core_DAO_Email',
  'getInfo' => fn() => [
    'title' => ts('Email'),
    'title_plural' => ts('Emails'),
    'description' => ts('Email information for a specific location.'),
    'log' => TRUE,
    'add' => '1.1',
    'icon' => 'fa-envelope-o',
    'label_field' => 'email',
  ],
  'getIndices' => fn() => [
    'index_location_type' => [
      'fields' => [
        'location_type_id' => TRUE,
      ],
      'add' => '2.0',
    ],
    'UI_email' => [
      'fields' => [
        'email' => TRUE,
      ],
      'add' => '1.5',
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
      'title' => ts('Email ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Email ID'),
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
      'title' => ts('Email Location Type'),
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
    'email' => [
      'title' => ts('Email'),
      'sql_type' => 'varchar(254)',
      'input_type' => 'Email',
      'description' => ts('Email address'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
      ],
    ],
    'is_primary' => [
      'title' => ts('Is Primary'),
      'sql_type' => 'boolean',
      'input_type' => 'Radio',
      'required' => TRUE,
      'description' => ts('Is this the primary email address'),
      'add' => '1.1',
      'default' => FALSE,
    ],
    'is_billing' => [
      'title' => ts('Is Billing Email?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this the billing?'),
      'add' => '2.0',
      'default' => FALSE,
    ],
    'on_hold' => [
      'title' => ts('On Hold'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'required' => TRUE,
      'description' => ts('Implicit FK to civicrm_option_value where option_group = email_on_hold.'),
      'add' => '1.1',
      'default' => 0,
      'usage' => [
        'export',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_PseudoConstant', 'emailOnHoldOptions'],
      ],
    ],
    'is_bulkmail' => [
      'title' => ts('Use for Bulk Mail'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this address for bulk mail ?'),
      'add' => '1.9',
      'default' => FALSE,
      'usage' => [
        'export',
      ],
    ],
    'hold_date' => [
      'title' => ts('Hold Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When the address went on bounce hold'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Hold Date'),
        'format_type' => 'activityDateTime',
      ],
    ],
    'reset_date' => [
      'title' => ts('Reset Date'),
      'sql_type' => 'datetime',
      'input_type' => 'Select Date',
      'description' => ts('When the address bounce status was last reset'),
      'add' => '1.1',
      'input_attrs' => [
        'label' => ts('Reset Date'),
        'format_type' => 'activityDateTime',
      ],
    ],
    'signature_text' => [
      'title' => ts('Signature Text'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Text formatted signature for the email.'),
      'add' => '3.2',
      'default' => NULL,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Signature Text'),
      ],
    ],
    'signature_html' => [
      'title' => ts('Signature Html'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('HTML formatted signature for the email.'),
      'add' => '3.2',
      'default' => NULL,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Signature HTML'),
      ],
    ],
  ],
];
