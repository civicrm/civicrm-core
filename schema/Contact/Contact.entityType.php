<?php

return [
  'name' => 'Contact',
  'table' => 'civicrm_contact',
  'class' => 'CRM_Contact_DAO_Contact',
  'getInfo' => fn() => [
    'title' => ts('Contact'),
    'title_plural' => ts('Contacts'),
    'description' => ts('Individuals, organizations, households, etc.'),
    'log' => TRUE,
    'add' => '1.1',
    'icon' => 'fa-address-book-o',
    'label_field' => 'display_name',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/contact/add?reset=1&ct=[contact_type]',
    'view' => 'civicrm/contact/view?reset=1&cid=[id]',
    'update' => 'civicrm/contact/add?reset=1&action=update&cid=[id]',
    'delete' => 'civicrm/contact/view/delete?reset=1&delete=1&cid=[id]',
  ],
  'getIndices' => fn() => [
    'index_contact_type' => [
      'fields' => [
        'contact_type' => TRUE,
      ],
      'add' => '2.1',
    ],
    'UI_external_identifier' => [
      'fields' => [
        'external_identifier' => TRUE,
      ],
      'unique' => TRUE,
      'add' => '1.7',
    ],
    'index_organization_name' => [
      'fields' => [
        'organization_name' => TRUE,
      ],
      'add' => '1.8',
    ],
    'index_contact_sub_type' => [
      'fields' => [
        'contact_sub_type' => TRUE,
      ],
      'add' => '2.1',
    ],
    'index_first_name' => [
      'fields' => [
        'first_name' => TRUE,
      ],
      'add' => '1.8',
    ],
    'index_last_name' => [
      'fields' => [
        'last_name' => TRUE,
      ],
      'add' => '1.8',
    ],
    'index_sort_name' => [
      'fields' => [
        'sort_name' => TRUE,
      ],
      'add' => '2.1',
    ],
    'index_preferred_communication_method' => [
      'fields' => [
        'preferred_communication_method' => TRUE,
      ],
      'add' => '1.6',
    ],
    'index_hash' => [
      'fields' => [
        'hash' => TRUE,
      ],
      'add' => '2.1',
    ],
    'index_api_key' => [
      'fields' => [
        'api_key' => TRUE,
      ],
      'add' => '2.1',
    ],
    'UI_prefix' => [
      'fields' => [
        'prefix_id' => TRUE,
      ],
      'add' => '1.6',
    ],
    'UI_suffix' => [
      'fields' => [
        'suffix_id' => TRUE,
      ],
      'add' => '1.6',
    ],
    'index_communication_style_id' => [
      'fields' => [
        'communication_style_id' => TRUE,
      ],
      'add' => '4.4',
    ],
    'UI_gender' => [
      'fields' => [
        'gender_id' => TRUE,
      ],
      'add' => '1.6',
    ],
    'index_is_deceased' => [
      'fields' => [
        'is_deceased' => TRUE,
      ],
      'add' => '4.7',
    ],
    'index_household_name' => [
      'fields' => [
        'household_name' => TRUE,
      ],
      'add' => '1.8',
    ],
    'index_is_deleted_sort_name' => [
      'fields' => [
        'is_deleted' => TRUE,
        'sort_name' => TRUE,
        'id' => TRUE,
      ],
      'add' => '4.4',
    ],
    'index_created_date' => [
      'fields' => [
        'created_date' => TRUE,
      ],
      'add' => '5.18',
    ],
    'index_modified_date' => [
      'fields' => [
        'modified_date' => TRUE,
      ],
      'add' => '5.18',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Contact ID'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'contact_type' => [
      'title' => ts('Contact Type'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Select',
      'readonly' => TRUE,
      'description' => ts('Type of Contact.'),
      'add' => '1.1',
      'usage' => [
        'export',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_contact_type',
        'key_column' => 'name',
        'label_column' => 'label',
        'icon_column' => 'icon',
        'condition' => 'parent_id IS NULL',
      ],
    ],
    'external_identifier' => [
      'title' => ts('External Identifier'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Unique trusted external ID (generally from a legacy app/datasource). Particularly useful for deduping operations.'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '8',
        'label' => ts('External Identifier'),
      ],
    ],
    'display_name' => [
      'title' => ts('Display Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'readonly' => TRUE,
      'description' => ts('Formatted name representing preferred format for display/print/other output.'),
      'add' => '1.1',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'size' => '30',
      ],
    ],
    'organization_name' => [
      'title' => ts('Organization Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Organization Name.'),
      'add' => '1.1',
      'contact_type' => 'Organization',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
        'label' => ts('Organization Name'),
      ],
    ],
    'contact_sub_type' => [
      'title' => ts('Contact Subtype'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('May be used to over-ride contact view and edit templates.'),
      'add' => '1.5',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'multiple' => TRUE,
        'control_field' => 'contact_type',
      ],
      'pseudoconstant' => [
        'table' => 'civicrm_contact_type',
        'key_column' => 'name',
        'label_column' => 'label',
        'icon_column' => 'icon',
        'condition' => 'parent_id IS NOT NULL',
        'condition_provider' => ['CRM_Contact_BAO_Contact', 'alterContactSubType'],
      ],
    ],
    'first_name' => [
      'title' => ts('First Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('First Name.'),
      'add' => '1.1',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
        'label' => ts('First Name'),
      ],
    ],
    'middle_name' => [
      'title' => ts('Middle Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Middle Name.'),
      'add' => '1.1',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
        'label' => ts('Middle Name'),
      ],
    ],
    'last_name' => [
      'title' => ts('Last Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Last Name.'),
      'add' => '1.1',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
        'label' => ts('Last Name'),
      ],
    ],
    'do_not_email' => [
      'title' => ts('Do Not Email'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '1.1',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Do Not Email'),
      ],
    ],
    'do_not_phone' => [
      'title' => ts('Do Not Phone'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '1.1',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Do Not Phone'),
      ],
    ],
    'do_not_mail' => [
      'title' => ts('Do Not Mail'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '1.1',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Do Not Mail'),
      ],
    ],
    'do_not_sms' => [
      'title' => ts('Do Not Sms'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '3.0',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Do Not Sms'),
      ],
    ],
    'do_not_trade' => [
      'title' => ts('Do Not Trade'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '1.1',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Do Not Trade'),
      ],
    ],
    'is_opt_out' => [
      'title' => ts('No Bulk Emails (User Opt Out)'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Has the contact opted out from receiving all bulk email from the organization or site domain?'),
      'add' => '1.1',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Is Opt Out'),
      ],
    ],
    'legal_identifier' => [
      'title' => ts('Legal Identifier'),
      'sql_type' => 'varchar(32)',
      'input_type' => 'Text',
      'description' => ts('May be used for SSN, EIN/TIN, Household ID (census) or other applicable unique legal/government ID.'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Legal Identifier'),
      ],
    ],
    'sort_name' => [
      'title' => ts('Sort Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'readonly' => TRUE,
      'description' => ts('Name used for sorting different contact types'),
      'add' => '1.1',
      'usage' => [
        'duplicate_matching',
        'export',
      ],
      'input_attrs' => [
        'size' => '30',
      ],
    ],
    'nick_name' => [
      'title' => ts('Nickname'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Nickname.'),
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
    'legal_name' => [
      'title' => ts('Legal Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Legal Name.'),
      'add' => '1.1',
      'contact_type' => 'Organization',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
        'label' => ts('Legal Name'),
      ],
    ],
    'image_URL' => [
      'title' => ts('Image Url'),
      'sql_type' => 'text',
      'input_type' => 'File',
      'description' => ts('optional URL for preferred image (photo, logo, etc.) to display for this contact.'),
      'add' => '1.1',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
        'label' => ts('Image'),
      ],
    ],
    'preferred_communication_method' => [
      'title' => ts('Preferred Communication Method'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Select',
      'description' => ts('What is the preferred mode of communication.'),
      'add' => '1.1',
      'serialize' => CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'multiple' => TRUE,
      ],
      'pseudoconstant' => [
        'option_group_name' => 'preferred_communication_method',
      ],
    ],
    'preferred_language' => [
      'title' => ts('Preferred Language'),
      'sql_type' => 'varchar(5)',
      'input_type' => 'Select',
      'description' => ts('Which language is preferred for communication. FK to languages in civicrm_option_value.'),
      'add' => '3.2',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'languages',
        'key_column' => 'name',
      ],
    ],
    'hash' => [
      'title' => ts('Contact Hash'),
      'sql_type' => 'varchar(32)',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('Key for validating requests related to this contact.'),
      'add' => '1.1',
      'usage' => [
        'export',
      ],
    ],
    'api_key' => [
      'title' => ts('Api Key'),
      'sql_type' => 'varchar(32)',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('API Key for validating requests related to this contact.'),
      'add' => '2.2',
      'permission' => [
        [
          'administer CiviCRM',
          'edit api keys',
        ],
      ],
      'input_attrs' => [
        'label' => ts('API KEY'),
      ],
    ],
    'source' => [
      'title' => ts('Contact Source'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('where contact come from, e.g. import, donate module insert...'),
      'add' => '1.1',
      'unique_name' => 'contact_source',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
      ],
    ],
    'prefix_id' => [
      'title' => ts('Individual Prefix'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Prefix or Title for name (Ms, Mr...). FK to prefix ID'),
      'add' => '1.2',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'individual_prefix',
      ],
    ],
    'suffix_id' => [
      'title' => ts('Individual Suffix'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Suffix for name (Jr, Sr...). FK to suffix ID'),
      'add' => '1.2',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'individual_suffix',
      ],
    ],
    'formal_title' => [
      'title' => ts('Formal Title'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Formal (academic or similar) title in front of name. (Prof., Dr. etc.)'),
      'add' => '4.5',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Formal Title'),
      ],
    ],
    'communication_style_id' => [
      'title' => ts('Communication Style'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Communication style (e.g. formal vs. familiar) to use with this contact. FK to communication styles in civicrm_option_value.'),
      'add' => '4.4',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'communication_style',
      ],
    ],
    'email_greeting_id' => [
      'title' => ts('Email Greeting ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to civicrm_option_value.id, that has to be valid registered Email Greeting.'),
      'add' => '3.0',
      'usage' => [
        'export',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'email_greeting',
      ],
    ],
    'email_greeting_custom' => [
      'title' => ts('Email Greeting Custom'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Custom Email Greeting.'),
      'add' => '3.0',
      'usage' => [
        'import',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Email Greeting Custom'),
      ],
    ],
    'email_greeting_display' => [
      'title' => ts('Email Greeting'),
      'sql_type' => 'varchar(255)',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('Cache Email Greeting.'),
      'add' => '3.0',
    ],
    'postal_greeting_id' => [
      'title' => ts('Postal Greeting ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to civicrm_option_value.id, that has to be valid registered Postal Greeting.'),
      'add' => '3.0',
      'usage' => [
        'export',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'postal_greeting',
      ],
    ],
    'postal_greeting_custom' => [
      'title' => ts('Postal Greeting Custom'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Custom Postal greeting.'),
      'add' => '3.0',
      'usage' => [
        'import',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Postal Greeting Custom'),
      ],
    ],
    'postal_greeting_display' => [
      'title' => ts('Postal Greeting'),
      'sql_type' => 'varchar(255)',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('Cache Postal greeting.'),
      'add' => '3.0',
    ],
    'addressee_id' => [
      'title' => ts('Addressee ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to civicrm_option_value.id, that has to be valid registered Addressee.'),
      'add' => '3.0',
      'usage' => [
        'export',
      ],
      'pseudoconstant' => [
        'option_group_name' => 'addressee',
      ],
    ],
    'addressee_custom' => [
      'title' => ts('Addressee Custom'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Custom Addressee.'),
      'add' => '3.0',
      'usage' => [
        'import',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Addressee Custom'),
      ],
    ],
    'addressee_display' => [
      'title' => ts('Addressee'),
      'sql_type' => 'varchar(255)',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('Cache Addressee.'),
      'add' => '3.0',
    ],
    'job_title' => [
      'title' => ts('Job Title'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('Job Title'),
      'add' => '1.1',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
        'label' => ts('Job Title'),
      ],
    ],
    'gender_id' => [
      'title' => ts('Gender ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('FK to gender ID'),
      'add' => '1.2',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Gender'),
      ],
      'pseudoconstant' => [
        'option_group_name' => 'gender',
      ],
    ],
    'birth_date' => [
      'title' => ts('Birth Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Date of birth'),
      'add' => '1.1',
      'contact_type' => 'Individual',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'birth',
        'label' => ts('Birth Date'),
      ],
    ],
    'is_deceased' => [
      'title' => ts('Deceased / Closed'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '1.1',
      'default' => FALSE,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('Is Deceased / Closed'),
      ],
    ],
    'deceased_date' => [
      'title' => ts('Deceased / Closed Date'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('Date deceased / closed'),
      'add' => '1.5',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'format_type' => 'birth',
        'label' => ts('Deceased / Closed Date'),
      ],
    ],
    'household_name' => [
      'title' => ts('Household Name'),
      'sql_type' => 'varchar(128)',
      'input_type' => 'Text',
      'description' => ts('Household Name.'),
      'add' => '1.1',
      'contact_type' => 'Household',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'size' => '30',
        'label' => ts('Household Name'),
      ],
    ],
    'primary_contact_id' => [
      'title' => ts('Household Primary Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('Optional FK to Primary Contact for this household.'),
      'add' => '1.1',
      'contact_type' => 'Household',
      'input_attrs' => [
        'label' => ts('Household Primary Contact'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'sic_code' => [
      'title' => ts('Sic Code'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Text',
      'description' => ts('Standard Industry Classification Code.'),
      'add' => '1.1',
      'contact_type' => 'Organization',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'input_attrs' => [
        'label' => ts('SIC Code'),
      ],
    ],
    'user_unique_id' => [
      'title' => ts('Unique ID (OpenID)'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'deprecated' => TRUE,
      'description' => ts('the OpenID (or OpenID-style http://username.domain/) unique identifier for this contact mainly used for logging in to CiviCRM'),
      'add' => '2.0',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'employer_id' => [
      'title' => ts('Current Employer ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('OPTIONAL FK to civicrm_contact record.'),
      'add' => '2.1',
      'unique_name' => 'current_employer_id',
      'contact_type' => 'Individual',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Current Employer'),
        'filter' => [
          'contact_type' => 'Organization',
        ],
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'is_deleted' => [
      'title' => ts('Contact is in Trash'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'add' => '3.2',
      'unique_name' => 'contact_is_deleted',
      'default' => FALSE,
      'usage' => [
        'export',
      ],
    ],
    'created_date' => [
      'title' => ts('Created Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the contact was created.'),
      'add' => '4.3',
      'default' => NULL,
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
        'label' => ts('Created Date'),
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When was the contact (or closely related entity) was created or modified or deleted.'),
      'add' => '4.3',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'format_type' => 'activityDateTime',
        'label' => ts('Modified Date'),
      ],
    ],
    'preferred_mail_format' => [
      'title' => ts('Preferred Mail Format'),
      'sql_type' => 'varchar(8)',
      'input_type' => 'Select',
      'deprecated' => TRUE,
      'description' => ts('Deprecated setting for text vs html mailings'),
      'add' => '1.1',
      'default' => 'Both',
      'input_attrs' => [
        'label' => ts('Preferred Mail Format'),
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Core_SelectValues', 'pmf'],
      ],
    ],
  ],
];
