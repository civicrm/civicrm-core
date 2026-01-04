<?php

return [
  'name' => 'StatusPreference',
  'table' => 'civicrm_status_pref',
  'class' => 'CRM_Core_DAO_StatusPreference',
  'getInfo' => fn() => [
    'title' => ts('Status Preference'),
    'title_plural' => ts('Status Preferences'),
    'description' => ts('Preferences controlling status checks called in system.check.'),
    'add' => '4.7',
  ],
  'getIndices' => fn() => [
    'UI_status_pref_name' => [
      'fields' => [
        'name' => TRUE,
      ],
      'add' => '4.7',
    ],
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Status Preference ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'description' => ts('Unique Status Preference ID'),
      'add' => '4.7',
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'domain_id' => [
      'title' => ts('Domain ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'required' => TRUE,
      'description' => ts('Which Domain is this Status Preference for'),
      'add' => '4.7',
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
    'name' => [
      'title' => ts('Status Check Name'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'required' => TRUE,
      'description' => ts('Name of the status check this preference references.'),
      'add' => '4.7',
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'hush_until' => [
      'title' => ts('Snooze Status Notifications Until'),
      'sql_type' => 'date',
      'input_type' => 'Select Date',
      'description' => ts('expires ignore_severity. NULL never hushes.'),
      'add' => '4.7',
      'default' => NULL,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
    ],
    'ignore_severity' => [
      'title' => ts('Ignore Severity'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Select',
      'description' => ts('Hush messages up to and including this severity.'),
      'add' => '4.7',
      'default' => 1,
      'usage' => [
        'import',
        'export',
        'duplicate_matching',
      ],
      'pseudoconstant' => [
        'callback' => ['CRM_Utils_Check', 'getSeverityOptions'],
      ],
    ],
    'prefs' => [
      'title' => ts('Status Preferences'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('These settings are per-check, and can\'t be compared across checks.'),
      'add' => '4.7',
    ],
    'check_info' => [
      'title' => ts('Check Info'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('These values are per-check, and can\'t be compared across checks.'),
      'add' => '4.7',
    ],
    'is_active' => [
      'title' => ts('Check Is Active'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this status check active?'),
      'add' => '5.19',
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
  ],
];
